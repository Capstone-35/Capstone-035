<?php

namespace App\Domain\Ordering;

use App\Domain\Loyalty\LoyaltyService;
use App\Domain\Payment\PaymentService;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        protected OrderPricingService $pricingService,
        protected OrderStateMachine $stateMachine,
        protected PaymentService $paymentService,
        protected LoyaltyService $loyaltyService
    ) {}

    /**
     * Creates a new Order (from QR checkout or POS manual order) using a single transaction mechanism (FR-06, D9).
     * Idempotent via unique client_request_id (NFR-06).
     *
     * @param  array{
     *   client_request_id?: string,
     *   source?: string,
     *   table_id?: ?int,
     *   member_id?: ?int,
     *   customer_name: string,
     *   customer_email?: ?string,
     *   customer_phone?: ?string,
     *   items: array<int, array{product_id: int, quantity: int, note?: ?string}>,
     *   promotion_id?: ?int,
     *   reward_redemption_id?: ?int,
     *   payment_method?: ?string,
     *   created_by?: ?int
     * }  $data
     */
    public function create(array $data): Order
    {
        $clientRequestId = (string) ($data['client_request_id'] ?? Str::uuid()->toString());

        $existing = Order::query()->where('client_request_id', $clientRequestId)->first();
        if ($existing) {
            return $existing->load(['items', 'table', 'member', 'payments']);
        }

        $member = ! empty($data['member_id']) ? Member::find($data['member_id']) : null;

        $pricing = $this->pricingService->calculate(
            $data['items'] ?? [],
            $member,
            ! empty($data['promotion_id']) ? (int) $data['promotion_id'] : null,
            ! empty($data['reward_redemption_id']) ? (int) $data['reward_redemption_id'] : null,
            true
        );

        try {
            return DB::transaction(function () use ($data, $clientRequestId, $member, $pricing) {
                $existingInsideTx = Order::query()->where('client_request_id', $clientRequestId)->first();
                if ($existingInsideTx) {
                    return $existingInsideTx->load(['items', 'table', 'member', 'payments']);
                }

                $orderNumber = $this->generateOrderNumber();
                $orderCode = $this->generateOrderCode();
                $publicToken = Str::random(40);
                $source = in_array($data['source'] ?? 'qr', ['qr', 'pos'], true) ? $data['source'] : 'qr';

                $priorSuccessful = false;
                if ($member) {
                    $priorSuccessful = Order::query()
                        ->successful()
                        ->where('member_id', $member->id)
                        ->exists();
                }

                $order = Order::query()->create([
                    'order_number' => $orderNumber,
                    'order_code' => $orderCode,
                    'public_token' => $publicToken,
                    'client_request_id' => $clientRequestId,
                    'source' => $source,
                    'table_id' => $data['table_id'] ?? null,
                    'member_id' => $member?->id,
                    'customer_name' => trim(strip_tags((string) $data['customer_name'])),
                    'customer_email' => ! empty($data['customer_email']) ? trim((string) $data['customer_email']) : ($member?->user?->email),
                    'customer_phone' => ! empty($data['customer_phone']) ? trim((string) $data['customer_phone']) : ($member?->user?->phone),
                    'status' => OrderStatus::MenungguPembayaran,
                    'payment_method' => null,
                    'subtotal' => $pricing['subtotal'],
                    'discount_amount' => $pricing['discount_amount'],
                    'tax_rate' => $pricing['tax_rate'],
                    'tax_amount' => $pricing['tax_amount'],
                    'total_amount' => $pricing['total_amount'],
                    'promotion_id' => $pricing['promotion_id'],
                    'reward_redemption_id' => $pricing['reward_redemption_id'],
                    'is_repeat_order' => $priorSuccessful,
                    'created_by' => $data['created_by'] ?? null,
                    'is_demo' => false,
                ]);

                foreach ($pricing['items'] as $item) {
                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'unit_price' => $item['unit_price'],
                        'quantity' => $item['quantity'],
                        'note' => $item['note'],
                        'line_total' => $item['line_total'],
                    ]);
                }

                OrderStatusHistory::query()->create([
                    'order_id' => $order->id,
                    'from_status' => null,
                    'to_status' => OrderStatus::MenungguPembayaran->value,
                    'actor_user_id' => $data['created_by'] ?? ($member?->user_id),
                    'source' => $source === 'pos' ? 'pos' : 'customer',
                    'reason' => $source === 'pos' ? 'Pesanan manual dibuat melalui POS' : 'Pesanan mandiri melalui QR meja',
                    'created_at' => now(),
                ]);

                if (! empty($data['payment_method'])) {
                    $method = PaymentMethod::tryFrom((string) $data['payment_method']);
                    if ($method) {
                        $this->paymentService->selectPaymentMethod($order, $method);
                    }
                }

                return $order->fresh(['items', 'table', 'member', 'payments', 'statusHistories']);
            });
        } catch (UniqueConstraintViolationException) {
            return Order::query()
                ->where('client_request_id', $clientRequestId)
                ->with(['items', 'table', 'member', 'payments'])
                ->firstOrFail();
        }
    }

    /**
     * D8: Items can be added/edited/removed ONLY while status is Menunggu Pembayaran.
     * After Dibayar the order is locked.
     *
     * @param  array<int, array{product_id: int, quantity: int, note?: ?string}>  $items
     */
    public function updateItemsWhileUnpaid(Order $order, array $items, ?User $actor = null): Order
    {
        return DB::transaction(function () use ($order, $items, $actor) {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== OrderStatus::MenungguPembayaran) {
                throw ValidationException::withMessages([
                    'status' => 'Item pesanan tidak dapat diubah karena status pesanan bukan lagi Menunggu Pembayaran.',
                ]);
            }

            $pricing = $this->pricingService->calculate(
                $items,
                $locked->member,
                $locked->promotion_id,
                $locked->reward_redemption_id,
                true
            );

            $locked->items()->delete();

            foreach ($pricing['items'] as $item) {
                OrderItem::query()->create([
                    'order_id' => $locked->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'note' => $item['note'],
                    'line_total' => $item['line_total'],
                ]);
            }

            $locked->subtotal = $pricing['subtotal'];
            $locked->discount_amount = $pricing['discount_amount'];
            $locked->tax_rate = $pricing['tax_rate'];
            $locked->tax_amount = $pricing['tax_amount'];
            $locked->total_amount = $pricing['total_amount'];
            $locked->save();

            // Update any pending payment amount
            Payment::query()
                ->where('order_id', $locked->id)
                ->where('status', PaymentStatus::Pending->value)
                ->update(['amount' => $locked->total_amount]);

            OrderStatusHistory::query()->create([
                'order_id' => $locked->id,
                'from_status' => $locked->status->value,
                'to_status' => $locked->status->value,
                'actor_user_id' => $actor?->id,
                'source' => 'pos',
                'reason' => 'Rincian item pesanan diperbarui saat Menunggu Pembayaran',
                'created_at' => now(),
            ]);

            return $locked->fresh(['items', 'payments', 'statusHistories']);
        });
    }

    /**
     * Kitchen starts preparing an order: Dibayar -> Diproses (UC-06).
     */
    public function startProcessing(Order $order, User $actor): Order
    {
        if (! $actor->can('kds.update_status')) {
            abort(403, 'Anda tidak memiliki izin untuk memperbarui status KDS.');
        }

        return $this->stateMachine->transition(
            $order,
            OrderStatus::Diproses,
            $actor,
            'kds',
            'Kitchen memulai pemrosesan pesanan',
            OrderStatus::Dibayar
        );
    }

    /**
     * Kitchen marks an order ready: Diproses -> Siap (UC-06, D11 quality confirmation).
     */
    public function markReady(Order $order, User $actor, bool $qualityConfirmed = true): Order
    {
        if (! $actor->can('kds.update_status')) {
            abort(403, 'Anda tidak memiliki izin untuk memperbarui status KDS.');
        }

        if (! $qualityConfirmed) {
            throw ValidationException::withMessages([
                'quality_confirmed' => 'Konfirmasi kesesuaian pesanan diperlukan sebelum menandai Siap.',
            ]);
        }

        return $this->stateMachine->transition(
            $order,
            OrderStatus::Siap,
            $actor,
            'kds',
            'Pesanan selesai disiapkan dan telah diverifikasi sesuai',
            OrderStatus::Diproses
        );
    }

    /**
     * Handover ("Pelayan" function, D3): verifies 4-char order_code, transitions Siap -> Selesai,
     * and triggers Loyalty / RFM / Promo Return Tracking when earn_on_status = Selesai.
     */
    public function handover(Order $order, User $actor, string $orderCodeInput): Order
    {
        if (! $actor->can('orders.handover')) {
            abort(403, 'Anda tidak memiliki izin penyerahan pesanan (handover).');
        }

        if (strtoupper(trim($orderCodeInput)) !== strtoupper($order->order_code)) {
            throw ValidationException::withMessages([
                'order_code' => 'Kode pesanan pelanggan tidak sesuai. Periksa kembali kode 4 karakter pada layar pelanggan.',
            ]);
        }

        $completedOrder = $this->stateMachine->transition(
            $order,
            OrderStatus::Selesai,
            $actor,
            $actor->hasRole('kitchen') ? 'kds' : 'pos',
            "Pesanan diserahkan kepada pelanggan (kode {$order->order_code} terverifikasi)",
            OrderStatus::Siap
        );

        $this->loyaltyService->awardIfEligible($completedOrder);

        return $completedOrder->fresh(['items', 'member', 'invoice', 'statusHistories']);
    }

    /**
     * Cancels an Order per D10 rules:
     * - Menunggu Pembayaran: Customer (own), Kasir, or Manager
     * - Dibayar / Diproses: Manager ONLY, reason required; reverses points & releases voucher/promo
     * - Siap / Selesai / Dibatalkan: rejected
     */
    public function cancel(Order $order, ?User $actor = null, ?string $reason = null, string $source = 'customer'): Order
    {
        if (in_array($order->status, [OrderStatus::Siap, OrderStatus::Selesai, OrderStatus::Dibatalkan], true)) {
            throw ValidationException::withMessages([
                'status' => "Pesanan berstatus '{$order->status->value}' sudah bersifat final dan tidak dapat dibatalkan.",
            ]);
        }

        if (in_array($order->status, [OrderStatus::Dibayar, OrderStatus::Diproses], true)) {
            if (! $actor || ! $actor->can('orders.cancel_paid')) {
                abort(403, 'Pembatalan setelah pembayaran hanya dapat dilakukan oleh Manager.');
            }
            if (blank($reason)) {
                throw ValidationException::withMessages([
                    'reason' => 'Alasan pembatalan wajib diisi untuk pesanan yang sudah dibayar.',
                ]);
            }
        }

        $cancelledOrder = $this->stateMachine->transition(
            $order,
            OrderStatus::Dibatalkan,
            $actor,
            $source,
            $reason ?: 'Dibatalkan sebelum pembayaran'
        );

        // Cancel any pending payments
        Payment::query()
            ->where('order_id', $cancelledOrder->id)
            ->where('status', PaymentStatus::Pending->value)
            ->update([
                'status' => PaymentStatus::Cancelled->value,
                'failure_reason' => 'Pesanan dibatalkan',
            ]);

        // Ensure cancelled order never keeps points or consumes promo/reward voucher (NFR-16)
        $this->loyaltyService->reverseForCancelledOrder($cancelledOrder);

        return $cancelledOrder->fresh(['items', 'payments', 'statusHistories']);
    }

    protected function generateOrderNumber(): string
    {
        $datePrefix = now()->timezone('Asia/Jakarta')->format('Ymd');
        $prefix = "774-{$datePrefix}-";

        $lastOrder = Order::query()
            ->where('order_number', 'like', "{$prefix}%")
            ->orderByDesc('order_number')
            ->lockForUpdate()
            ->first();

        $nextSeq = 1;
        if ($lastOrder) {
            $parts = explode('-', $lastOrder->order_number);
            $lastSeq = (int) end($parts);
            $nextSeq = $lastSeq + 1;
        }

        $candidate = $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
        while (Order::query()->where('order_number', $candidate)->exists()) {
            $nextSeq++;
            $candidate = $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }

    protected function generateOrderCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 4; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }
}
