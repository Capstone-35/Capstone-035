<?php

namespace App\Http\Controllers\Pos;

use App\Domain\Invoice\InvoiceService;
use App\Domain\Ordering\OrderPricingService;
use App\Domain\Ordering\OrderService;
use App\Domain\Payment\PaymentService;
use App\Domain\Promotion\PromotionEngine;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected OrderPricingService $pricingService,
        protected PaymentService $paymentService,
        protected InvoiceService $invoiceService,
        protected PromotionEngine $promotionEngine
    ) {}

    /**
     * D1 POS Dashboard: /pos
     */
    public function dashboard(): View
    {
        $this->paymentService->expireStaleQris();

        $todayStart = now()->startOfDay();

        $waitingPaymentCount = Order::query()
            ->where('status', OrderStatus::MenungguPembayaran->value)
            ->count();

        $activeKdsCount = Order::query()
            ->whereIn('status', [OrderStatus::Dibayar->value, OrderStatus::Diproses->value])
            ->count();

        $readyForHandoverCount = Order::query()
            ->where('status', OrderStatus::Siap->value)
            ->count();

        $todayRevenue = (int) Order::query()
            ->paidRevenue()
            ->where('created_at', '>=', $todayStart)
            ->sum('total_amount');

        $todayOrdersCount = Order::query()
            ->where('created_at', '>=', $todayStart)
            ->where('status', '!=', OrderStatus::Dibatalkan->value)
            ->count();

        $pendingCashOrders = Order::query()
            ->with(['table', 'items', 'member.user'])
            ->where('status', OrderStatus::MenungguPembayaran->value)
            ->orderBy('created_at')
            ->limit(15)
            ->get();

        $readyOrders = Order::query()
            ->with(['table', 'items', 'member.user'])
            ->where('status', OrderStatus::Siap->value)
            ->orderBy('ready_at')
            ->limit(15)
            ->get();

        $activeOrders = Order::query()
            ->with(['table', 'items', 'member.user', 'invoice.latestDelivery'])
            ->whereIn('status', [
                OrderStatus::MenungguPembayaran->value,
                OrderStatus::Dibayar->value,
                OrderStatus::Diproses->value,
                OrderStatus::Siap->value,
            ])
            ->latest('created_at')
            ->limit(25)
            ->get();

        return view('pos.dashboard', [
            'waitingPaymentCount' => $waitingPaymentCount,
            'activeKdsCount' => $activeKdsCount,
            'readyForHandoverCount' => $readyForHandoverCount,
            'todayRevenue' => $todayRevenue,
            'todayOrdersCount' => $todayOrdersCount,
            'pendingCashOrders' => $pendingCashOrders,
            'readyOrders' => $readyOrders,
            'activeOrders' => $activeOrders,
        ]);
    }

    /**
     * D2 Incoming QR Orders Queue: /pos/orders
     */
    public function orders(Request $request): View
    {
        $this->paymentService->expireStaleQris();

        $statusFilter = $request->query('status');
        $sourceFilter = $request->query('source');
        $search = trim((string) $request->query('q', ''));

        $query = Order::query()
            ->with(['table', 'items', 'member.user', 'latestPayment', 'invoice.latestDelivery'])
            ->latest('created_at');

        if (! empty($statusFilter)) {
            $query->where('status', $statusFilter);
        } else {
            $query->whereIn('status', [
                OrderStatus::MenungguPembayaran->value,
                OrderStatus::Dibayar->value,
                OrderStatus::Diproses->value,
                OrderStatus::Siap->value,
            ]);
        }

        if (! empty($sourceFilter)) {
            $query->where('source', $sourceFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('order_code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        return view('pos.orders', [
            'orders' => $orders,
            'statusFilter' => $statusFilter,
            'sourceFilter' => $sourceFilter,
            'search' => $search,
        ]);
    }

    /**
     * Order detail in POS: /pos/orders/{order:order_number}
     */
    public function showOrder(Order $order): View
    {
        $this->paymentService->expireStaleQris();

        $order->load([
            'table',
            'items.product',
            'member.user',
            'payments.confirmer',
            'invoice.deliveries',
            'statusHistories.actor',
            'promotion',
            'rewardRedemption.reward',
        ]);

        $products = Product::query()
            ->where('is_active', true)
            ->where('is_available', true)
            ->orderBy('name')
            ->get();

        $latestPayment = $order->payments->first();
        $qrisSvg = null;
        if ($latestPayment && $latestPayment->method === PaymentMethod::Qris && $latestPayment->qris_payload) {
            $options = new QROptions([
                'outputBase64' => false,
                'addQuietzone' => true,
            ]);
            $qrisSvg = (new QRCode($options))->render($latestPayment->qris_payload);
        }

        return view('pos.order-detail', [
            'order' => $order,
            'products' => $products,
            'latestPayment' => $latestPayment,
            'qrisSvg' => $qrisSvg,
        ]);
    }

    /**
     * D3 Manual POS Order Screen: /pos/new (FR-06, UC-05)
     */
    public function createManualOrder(Request $request): View
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $products = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        $tables = DiningTable::query()
            ->where('is_active', true)
            ->orderBy('number')
            ->get();

        $members = Member::query()
            ->with(['user', 'rewardRedemptions' => fn ($q) => $q->with('reward')->where('status', 'active')])
            ->orderBy('id')
            ->get();

        $promotions = \App\Models\Promotion::query()
            ->where('is_active', true)
            ->where('rule_type', '!=', \App\Enums\PromotionRuleType::ReturnTracking->value)
            ->get();

        return view('pos.manual-order', [
            'categories' => $categories,
            'products' => $products,
            'tables' => $tables,
            'members' => $members,
            'promotions' => $promotions,
            'taxRate' => (float) Setting::getValue('tax_rate', 0),
            'clientRequestId' => (string) Str::uuid(),
        ]);
    }

    /**
     * POST /pos/orders — Creates manual order via OrderService::create(source='pos') (FR-06)
     */
    public function storeManualOrder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_request_id' => ['nullable', 'string', 'max:64'],
            'table_id' => ['nullable', 'integer', 'exists:tables,id'],
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'customer_name' => ['required', 'string', 'min:2', 'max:80'],
            'customer_email' => ['nullable', 'email', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'payment_method' => ['required', 'in:tunai,qris'],
            'confirm_cash_now' => ['nullable', 'boolean'],
            'cash_received' => ['nullable', 'integer', 'min:0'],
            'promotion_id' => ['nullable', 'integer'],
            'reward_redemption_id' => ['nullable', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.note' => ['nullable', 'string', 'max:200'],
        ], [
            'customer_name.required' => 'Nama pelanggan wajib diisi.',
            'items.required' => 'Pilih minimal 1 menu untuk membuat pesanan.',
            'items.min' => 'Pilih minimal 1 menu untuk membuat pesanan.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
        ]);

        try {
            $order = $this->orderService->create([
                'client_request_id' => $validated['client_request_id'] ?? (string) Str::uuid(),
                'source' => 'pos',
                'table_id' => ! empty($validated['table_id']) ? (int) $validated['table_id'] : null,
                'member_id' => ! empty($validated['member_id']) ? (int) $validated['member_id'] : null,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'items' => $validated['items'],
                'promotion_id' => ! empty($validated['promotion_id']) ? (int) $validated['promotion_id'] : null,
                'reward_redemption_id' => ! empty($validated['reward_redemption_id']) ? (int) $validated['reward_redemption_id'] : null,
                'payment_method' => $validated['payment_method'],
                'created_by' => Auth::id(),
            ]);

            if (
                $validated['payment_method'] === 'tunai'
                && $request->boolean('confirm_cash_now')
                && $order->status === OrderStatus::MenungguPembayaran
            ) {
                $cashReceived = isset($validated['cash_received']) && $validated['cash_received'] > 0
                    ? (int) $validated['cash_received']
                    : (int) $order->total_amount;

                $this->paymentService->confirmCash($order, Auth::user(), $cashReceived);

                return redirect()->route('pos.orders.show', ['order' => $order->order_number])
                    ->with('toast_success', "Pesanan POS {$order->order_number} berhasil dibuat dan dikonfirmasi Dibayar (Tunai).");
            }

            return redirect()->route('pos.orders.show', ['order' => $order->order_number])
                ->with('toast_success', "Pesanan POS {$order->order_number} berhasil dibuat.");
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors())->with('toast_error', collect($e->errors())->flatten()->first());
        }
    }

    /**
     * D8: Items can be added/edited/removed ONLY while status is Menunggu Pembayaran.
     */
    public function updateOrderItems(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.note' => ['nullable', 'string', 'max:200'],
        ], [
            'items.required' => 'Pesanan harus memiliki minimal 1 item.',
        ]);

        try {
            $this->orderService->updateItemsWhileUnpaid($order, $validated['items'], Auth::user());

            return redirect()->route('pos.orders.show', ['order' => $order->order_number])
                ->with('toast_success', "Rincian item pesanan {$order->order_number} berhasil diperbarui.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('toast_error', collect($e->errors())->flatten()->first());
        }
    }

    /**
     * D4 & D5 Payment Verification Queue: /pos/payments
     */
    public function payments(): View
    {
        $this->paymentService->expireStaleQris();

        $pendingOrders = Order::query()
            ->with(['table', 'items', 'member.user', 'latestPayment'])
            ->where('status', OrderStatus::MenungguPembayaran->value)
            ->orderBy('created_at')
            ->get();

        $recentPaidOrders = Order::query()
            ->with(['table', 'paidPayment.confirmer', 'invoice.latestDelivery'])
            ->whereIn('status', [
                OrderStatus::Dibayar->value,
                OrderStatus::Diproses->value,
                OrderStatus::Siap->value,
                OrderStatus::Selesai->value,
            ])
            ->latest('paid_at')
            ->limit(15)
            ->get();

        return view('pos.payments', [
            'pendingOrders' => $pendingOrders,
            'recentPaidOrders' => $recentPaidOrders,
        ]);
    }

    /**
     * D4 Cash Payment Confirmation: POST /pos/orders/{order:order_number}/confirm-cash (UC-04, FR-05)
     */
    public function confirmCash(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'cash_received' => ['nullable', 'integer', 'min:0'],
        ]);

        $cashReceived = isset($validated['cash_received']) && $validated['cash_received'] !== null
            ? (int) $validated['cash_received']
            : (int) $order->total_amount;

        try {
            $paidOrder = $this->paymentService->confirmCash($order, Auth::user(), $cashReceived);
            $change = max(0, $cashReceived - (int) $paidOrder->total_amount);
            $changeFmt = 'Rp'.number_format($change, 0, ',', '.');

            return back()->with(
                'toast_success',
                "Pembayaran Tunai pesanan {$paidOrder->order_number} berhasil dikonfirmasi Dibayar. Kembalian: {$changeFmt}. Pesanan diteruskan ke KDS & Invoice Digital dikirim."
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('toast_error', collect($e->errors())->flatten()->first());
        }
    }

    /**
     * Cancel unpaid order from POS (D10): POST /pos/orders/{order:order_number}/cancel
     */
    public function cancelOrder(Request $request, Order $order): RedirectResponse
    {
        if ($order->status !== OrderStatus::MenungguPembayaran) {
            return back()->with(
                'toast_error',
                'Kasir hanya dapat membatalkan pesanan yang masih berstatus Menunggu Pembayaran. Hubungi Manager untuk pembatalan pesanan yang sudah dibayar.'
            );
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:200'],
        ]);

        try {
            $this->orderService->cancel(
                $order,
                Auth::user(),
                $validated['reason'] ?: 'Dibatalkan oleh Kasir sebelum pembayaran',
                'pos'
            );

            return back()->with('toast_info', "Pesanan {$order->order_number} berhasil dibatalkan.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('toast_error', collect($e->errors())->flatten()->first());
        }
    }

    /**
     * D3 Handover Action ("Serahkan ke Pelanggan"): POST /pos/orders/{order:order_number}/handover
     */
    public function handover(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'order_code' => ['required', 'string', 'max:8'],
        ], [
            'order_code.required' => 'Masukkan 4 karakter kode pesanan pelanggan untuk verifikasi penyerahan.',
        ]);

        try {
            $this->orderService->handover($order, Auth::user(), $validated['order_code']);

            return back()->with(
                'toast_success',
                "Pesanan {$order->order_number} (Kode {$order->order_code}) telah diserahkan ke pelanggan dan ditandai Selesai."
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('toast_error', collect($e->errors())->flatten()->first());
        }
    }

    /**
     * D6 Today's Active & Completed Transactions: /pos/transactions
     */
    public function transactions(Request $request): View
    {
        $statusFilter = $request->query('status');
        $methodFilter = $request->query('payment_method');
        $dateFilter = $request->query('date', now()->toDateString());
        $search = trim((string) $request->query('q', ''));

        $query = Order::query()
            ->with(['table', 'member.user', 'invoice.latestDelivery', 'paidPayment'])
            ->latest('created_at');

        if (! empty($dateFilter)) {
            $query->whereDate('created_at', $dateFilter);
        }

        if (! empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        if (! empty($methodFilter)) {
            $query->where('payment_method', $methodFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('order_code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        return view('pos.transactions', [
            'orders' => $orders,
            'statusFilter' => $statusFilter,
            'methodFilter' => $methodFilter,
            'dateFilter' => $dateFilter,
            'search' => $search,
        ]);
    }

    /**
     * D7 Invoice Delivery & Resend Screen: /pos/invoices (FR-08, UC-07)
     */
    public function invoices(Request $request): View
    {
        $statusFilter = $request->query('delivery_status');
        $search = trim((string) $request->query('q', ''));

        $query = Invoice::query()
            ->with(['order.table', 'order.member.user', 'deliveries', 'latestDelivery'])
            ->latest('issued_at');

        if (! empty($statusFilter)) {
            $query->whereHas('latestDelivery', function ($q) use ($statusFilter) {
                $q->where('status', $statusFilter);
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($oq) use ($search) {
                        $oq->where('order_number', 'like', "%{$search}%")
                            ->orWhere('customer_name', 'like', "%{$search}%")
                            ->orWhere('customer_email', 'like', "%{$search}%");
                    });
            });
        }

        $invoices = $query->paginate(20)->withQueryString();

        return view('pos.invoices', [
            'invoices' => $invoices,
            'statusFilter' => $statusFilter,
            'search' => $search,
        ]);
    }

    public function resendInvoice(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'channel' => ['required', 'in:email,whatsapp'],
            'recipient' => ['nullable', 'string', 'max:150'],
        ]);

        $delivery = $this->invoiceService->resend(
            $invoice,
            Auth::user(),
            $validated['channel'],
            $validated['recipient'] ?? null
        );

        return back()->with(
            'toast_success',
            "Invoice {$invoice->invoice_number} telah dijadwalkan kirim ulang melalui ".strtoupper($delivery->channel)." ke {$delivery->recipient}."
        );
    }

    /**
     * Quick product availability toggle (Habis / Tersedia, Assumption A6)
     */
    public function toggleProductAvailability(Product $product): RedirectResponse
    {
        if (! Auth::user()->can('products.toggle_availability')) {
            abort(403);
        }

        $product->update([
            'is_available' => ! $product->is_available,
        ]);

        $stateLabel = $product->is_available ? 'Tersedia' : 'Habis';

        return back()->with('toast_success', "Status ketersediaan menu '{$product->name}' diubah menjadi {$stateLabel}.");
    }

    /**
     * JSON feed for live POS polling (every 4s)
     */
    public function feed(): JsonResponse
    {
        $this->paymentService->expireStaleQris();

        $waitingCount = Order::query()->where('status', OrderStatus::MenungguPembayaran->value)->count();
        $readyCount = Order::query()->where('status', OrderStatus::Siap->value)->count();
        $activeKdsCount = Order::query()->whereIn('status', [OrderStatus::Dibayar->value, OrderStatus::Diproses->value])->count();

        $latestOrders = Order::query()
            ->with('table')
            ->whereIn('status', [
                OrderStatus::MenungguPembayaran->value,
                OrderStatus::Dibayar->value,
                OrderStatus::Diproses->value,
                OrderStatus::Siap->value,
            ])
            ->latest('updated_at')
            ->limit(15)
            ->get()
            ->map(fn (Order $o) => [
                'order_number' => $o->order_number,
                'order_code' => $o->order_code,
                'customer_name' => $o->customer_name,
                'table_label' => $o->table?->label ?? 'Takeaway/POS',
                'status' => $o->status->value,
                'payment_method' => $o->payment_method?->value,
                'total_amount' => $o->total_amount,
                'formatted_total' => $o->formattedTotal(),
                'updated_at' => $o->updated_at?->toIso8601String(),
                'url' => route('pos.orders.show', ['order' => $o->order_number]),
            ]);

        return response()->json([
            'waiting_payment_count' => $waitingCount,
            'ready_for_handover_count' => $readyCount,
            'active_kds_count' => $activeKdsCount,
            'orders' => $latestOrders,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
