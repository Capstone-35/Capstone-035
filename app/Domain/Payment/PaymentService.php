<?php

namespace App\Domain\Payment;

use App\Domain\Invoice\InvoiceService;
use App\Domain\Loyalty\LoyaltyService;
use App\Domain\Ordering\OrderStateMachine;
use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Drivers\MockQrisGateway;
use App\Domain\Promotion\PromotionEngine;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\RewardRedemption;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        protected OrderStateMachine $stateMachine,
        protected InvoiceService $invoiceService,
        protected LoyaltyService $loyaltyService,
        protected PromotionEngine $promotionEngine
    ) {}

    public function gateway(): PaymentGateway
    {
        return app(MockQrisGateway::class);
    }

    /**
     * Selects or switches the payment method for a pending order (Module D).
     */
    public function selectPaymentMethod(Order $order, PaymentMethod $method): Payment
    {
        return DB::transaction(function () use ($order, $method) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== OrderStatus::MenungguPembayaran) {
                throw ValidationException::withMessages([
                    'order' => 'Metode pembayaran tidak dapat diubah karena pesanan sudah dibayar atau dibatalkan.',
                ]);
            }

            // Cancel any previous pending payments on method switch
            Payment::query()
                ->where('order_id', $lockedOrder->id)
                ->where('status', PaymentStatus::Pending->value)
                ->update([
                    'status' => PaymentStatus::Cancelled->value,
                    'failure_reason' => 'Diganti ke metode pembayaran baru',
                ]);

            $lockedOrder->payment_method = $method;
            $lockedOrder->save();

            if ($method === PaymentMethod::Tunai) {
                return Payment::query()->create([
                    'order_id' => $lockedOrder->id,
                    'method' => PaymentMethod::Tunai,
                    'status' => PaymentStatus::Pending,
                    'amount' => $lockedOrder->total_amount,
                    'provider' => 'cash',
                ]);
            }

            // ASSUMPTION A1: QRIS payment window = 15 minutes
            $expiryMinutes = max(1, (int) Setting::getValue('qris_expiry_minutes', 15));
            $charge = $this->gateway()->createQrisCharge($lockedOrder, $expiryMinutes);

            return Payment::query()->create([
                'order_id' => $lockedOrder->id,
                'method' => PaymentMethod::Qris,
                'status' => PaymentStatus::Pending,
                'amount' => $lockedOrder->total_amount,
                'provider' => $charge['provider'],
                'provider_reference' => $charge['provider_reference'],
                'qris_payload' => $charge['qris_payload'],
                'expires_at' => $charge['expires_at'],
            ]);
        });
    }

    /**
     * Kasir confirms Tunai payment (UC-04, FR-05, NFR-06).
     * Row-locked so concurrent double confirms cannot create duplicate paid records.
     */
    public function confirmCash(Order $order, User $kasir, ?int $cashReceived = null): Order
    {
        if (! $kasir->can('orders.confirm_cash')) {
            abort(403, 'Anda tidak memiliki izin untuk mengonfirmasi pembayaran tunai.');
        }

        $paidOrder = DB::transaction(function () use ($order, $kasir, $cashReceived) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== OrderStatus::MenungguPembayaran) {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan ini sudah dikonfirmasi atau tidak lagi berstatus Menunggu Pembayaran.',
                ]);
            }

            // Guard: at most one paid payment per order
            if (Payment::query()->where('order_id', $lockedOrder->id)->where('status', PaymentStatus::Paid->value)->exists()) {
                throw ValidationException::withMessages([
                    'payment' => 'Pembayaran untuk pesanan ini sudah tercatat lunas.',
                ]);
            }

            $total = (int) $lockedOrder->total_amount;
            $received = $cashReceived !== null ? $cashReceived : $total;

            // UC-04 A-1: Nominal tunai tidak sesuai
            if ($received < $total) {
                throw ValidationException::withMessages([
                    'cash_received' => 'Nominal uang tunai yang diterima (Rp'.number_format($received, 0, ',', '.').') kurang dari total tagihan (Rp'.number_format($total, 0, ',', '.').').',
                ]);
            }

            $change = $received - $total;

            // Cancel any pending QRIS attempts if customer paid cash at counter
            Payment::query()
                ->where('order_id', $lockedOrder->id)
                ->where('status', PaymentStatus::Pending->value)
                ->where('method', PaymentMethod::Qris->value)
                ->update(['status' => PaymentStatus::Cancelled->value]);

            $pendingCash = Payment::query()
                ->where('order_id', $lockedOrder->id)
                ->where('status', PaymentStatus::Pending->value)
                ->where('method', PaymentMethod::Tunai->value)
                ->latest('id')
                ->first();

            if ($pendingCash) {
                $pendingCash->update([
                    'status' => PaymentStatus::Paid,
                    'amount' => $total,
                    'paid_at' => now(),
                    'confirmed_by' => $kasir->id,
                    'cash_received' => $received,
                    'change_amount' => $change,
                ]);
            } else {
                Payment::query()->create([
                    'order_id' => $lockedOrder->id,
                    'method' => PaymentMethod::Tunai,
                    'status' => PaymentStatus::Paid,
                    'amount' => $total,
                    'provider' => 'cash',
                    'paid_at' => now(),
                    'confirmed_by' => $kasir->id,
                    'cash_received' => $received,
                    'change_amount' => $change,
                ]);
            }

            $lockedOrder->payment_method = PaymentMethod::Tunai;
            $lockedOrder->save();

            return $this->stateMachine->transition(
                $lockedOrder,
                OrderStatus::Dibayar,
                $kasir,
                'pos',
                'Konfirmasi pembayaran tunai oleh Kasir',
                OrderStatus::MenungguPembayaran
            );
        });

        $this->afterOrderPaid($paidOrder);

        return $paidOrder->fresh(['items', 'payments', 'invoice', 'table', 'member']);
    }

    /**
     * Handles incoming QRIS webhook (UC-04 S-1, NFR-06).
     * Verifies HMAC signature, logs event idempotently, re-queries provider status, checks amount,
     * locks order, and transitions to Dibayar only when verified.
     *
     * @return array{http_status: int, result: string, message: string, order?: Order}
     */
    public function handleQrisWebhook(string $provider, array $payload, string $signature): array
    {
        $gateway = $this->gateway();
        $signatureValid = $gateway->verifyWebhookSignature($payload, $signature);

        $eventId = (string) ($payload['event_id'] ?? '');
        $providerReference = (string) ($payload['provider_reference'] ?? '');

        if (! $signatureValid) {
            Log::channel('payments')->warning('Invalid QRIS webhook signature rejected', [
                'provider' => $provider,
                'event_id' => $eventId,
                'provider_reference' => $providerReference,
            ]);

            return [
                'http_status' => 401,
                'result' => 'invalid_signature',
                'message' => 'Signature webhook tidak valid.',
            ];
        }

        if (blank($eventId) || blank($providerReference)) {
            return [
                'http_status' => 422,
                'result' => 'invalid_payload',
                'message' => 'Payload webhook tidak lengkap.',
            ];
        }

        // Replay / duplicate event idempotency check (NFR-06, UC-04 A-3)
        try {
            $eventRecord = PaymentWebhookEvent::query()->create([
                'provider' => $provider,
                'event_id' => $eventId,
                'payload' => $payload,
                'signature_valid' => true,
                'processed_at' => now(),
                'result' => 'processing',
            ]);
        } catch (UniqueConstraintViolationException) {
            Log::channel('payments')->info('Duplicate QRIS webhook event ignored idempotently', [
                'provider' => $provider,
                'event_id' => $eventId,
            ]);

            return [
                'http_status' => 200,
                'result' => 'duplicate_ignored',
                'message' => 'Notifikasi pembayaran ganda diabaikan (idempotent).',
            ];
        }

        // Re-query provider status (do not trust payload alone)
        $providerState = $gateway->fetchStatus($providerReference, $payload);
        if (! $providerState['provider_available']) {
            // UC-04 E-1: Provider timeout
            $eventRecord->update(['result' => 'provider_timeout']);

            return [
                'http_status' => 200,
                'result' => 'provider_timeout',
                'message' => 'Verifikasi belum dapat dilakukan. Status pesanan dipertahankan.',
            ];
        }

        $outcome = DB::transaction(function () use ($providerReference, $providerState, $eventRecord) {
            $payment = Payment::query()
                ->where('provider_reference', $providerReference)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                $eventRecord->update(['result' => 'payment_not_found']);

                return [
                    'http_status' => 404,
                    'result' => 'payment_not_found',
                    'message' => 'Referensi pembayaran tidak ditemukan.',
                    'just_paid' => false,
                ];
            }

            /** @var Order $order */
            $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->firstOrFail();

            // UC-04 A-3: If order is already Dibayar or later, ignore duplicate confirmation
            if ($order->status->isPaidOrLater()) {
                $eventRecord->update(['result' => 'already_paid_ignored']);

                return [
                    'http_status' => 200,
                    'result' => 'already_paid_ignored',
                    'message' => 'Transaksi sudah berstatus Dibayar. Pembaruan ganda diabaikan.',
                    'order' => $order,
                    'just_paid' => false,
                ];
            }

            if ($order->status === OrderStatus::Dibatalkan) {
                $eventRecord->update(['result' => 'order_cancelled']);

                return [
                    'http_status' => 200,
                    'result' => 'order_cancelled',
                    'message' => 'Pesanan sudah dibatalkan.',
                    'order' => $order,
                    'just_paid' => false,
                ];
            }

            $status = $providerState['status'];
            $reportedAmount = (int) $providerState['amount'];

            if ($status === 'paid') {
                // Verify exact amount match
                if ($reportedAmount !== (int) $order->total_amount) {
                    $payment->update([
                        'status' => PaymentStatus::AmountMismatch,
                        'failure_reason' => "Nominal QRIS (Rp{$reportedAmount}) tidak sesuai tagihan (Rp{$order->total_amount}).",
                    ]);
                    $eventRecord->update(['result' => 'amount_mismatch']);

                    return [
                        'http_status' => 200,
                        'result' => 'amount_mismatch',
                        'message' => 'Nominal pembayaran tidak sesuai tagihan. Status tetap Menunggu Pembayaran.',
                        'order' => $order,
                        'just_paid' => false,
                    ];
                }

                $payment->update([
                    'status' => PaymentStatus::Paid,
                    'paid_at' => now(),
                    'failure_reason' => null,
                ]);

                $order->payment_method = PaymentMethod::Qris;
                $order->save();

                $updatedOrder = $this->stateMachine->transition(
                    $order,
                    OrderStatus::Dibayar,
                    null,
                    'webhook',
                    "Pembayaran QRIS terverifikasi ({$providerReference})",
                    OrderStatus::MenungguPembayaran
                );

                $eventRecord->update(['result' => 'paid']);

                return [
                    'http_status' => 200,
                    'result' => 'paid',
                    'message' => 'Pembayaran QRIS berhasil diverifikasi.',
                    'order' => $updatedOrder,
                    'just_paid' => true,
                ];
            }

            if ($status === 'expired') {
                $payment->update([
                    'status' => PaymentStatus::Expired,
                    'failure_reason' => 'QRIS kedaluwarsa.',
                ]);
                $eventRecord->update(['result' => 'expired']);

                return [
                    'http_status' => 200,
                    'result' => 'expired',
                    'message' => 'QRIS kedaluwarsa. Pesanan tetap Menunggu Pembayaran.',
                    'order' => $order,
                    'just_paid' => false,
                ];
            }

            // Failed or rejected
            $payment->update([
                'status' => PaymentStatus::Failed,
                'failure_reason' => 'Pembayaran QRIS gagal pada penyedia.',
            ]);
            $eventRecord->update(['result' => 'failed']);

            return [
                'http_status' => 200,
                'result' => 'failed',
                'message' => 'Pembayaran QRIS belum berhasil. Status tetap Menunggu Pembayaran.',
                'order' => $order,
                'just_paid' => false,
            ];
        });

        if (! empty($outcome['just_paid']) && isset($outcome['order'])) {
            $this->afterOrderPaid($outcome['order']);
        }

        unset($outcome['just_paid']);

        return $outcome;
    }

    /**
     * Side effects triggered when an Order becomes Dibayar (Step 10 BPMN parallel split):
     * 1. Record promotion usage / mark reward voucher used
     * 2. Generate digital invoice & dispatch delivery job
     * 3. Award loyalty points if earn_on_status == Dibayar
     */
    public function afterOrderPaid(Order $order): void
    {
        // Mark reward redemption voucher as used
        if ($order->reward_redemption_id) {
            RewardRedemption::query()
                ->whereKey($order->reward_redemption_id)
                ->update([
                    'status' => 'used',
                    'used_order_id' => $order->id,
                ]);
        }

        // Record promotion usage
        if ($order->promotion_id) {
            $this->promotionEngine->recordUsageForPaidOrder($order);
        }

        // Generate invoice & dispatch delivery
        $this->invoiceService->generateForPaidOrder($order, true);

        // Award points if setting loyalty.earn_on_status is Dibayar
        $this->loyaltyService->awardIfEligible($order->fresh());
    }

    /**
     * Marks expired QRIS charges as 'expired' (scheduled every minute).
     */
    public function expireStaleQris(): int
    {
        return Payment::query()
            ->where('method', PaymentMethod::Qris->value)
            ->where('status', PaymentStatus::Pending->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update([
                'status' => PaymentStatus::Expired->value,
                'failure_reason' => 'Waktu pembayaran QRIS (15 menit) telah habis.',
            ]);
    }
}
