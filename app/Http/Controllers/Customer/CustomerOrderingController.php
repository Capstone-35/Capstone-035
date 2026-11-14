<?php

namespace App\Http\Controllers\Customer;

use App\Domain\Invoice\InvoiceService;
use App\Domain\Ordering\OrderPricingService;
use App\Domain\Ordering\OrderService;
use App\Domain\Payment\PaymentService;
use App\Domain\Promotion\PromotionEngine;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PromotionRuleType;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerOrderingController extends Controller
{
    public function __construct(
        protected OrderPricingService $pricingService,
        protected OrderService $orderService,
        protected PaymentService $paymentService,
        protected PromotionEngine $promotionEngine,
        protected InvoiceService $invoiceService
    ) {}

    public function home(Request $request): RedirectResponse
    {
        if (! $request->session()->has('qr_table_id')) {
            $firstTable = DiningTable::query()->where('is_active', true)->orderBy('number')->first();
            if ($firstTable) {
                return redirect()->route('table.scan', ['qr_token' => $firstTable->qr_token]);
            }
        }

        return redirect()->route('menu.index');
    }

    /**
     * A1 QR Landing: /t/{qr_token} (FR-01, BPMN Step 1–2)
     * Directly binds table to session and redirects to menu catalog.
     */
    public function scanTable(Request $request, string $qr_token): View|RedirectResponse
    {
        $table = DiningTable::query()
            ->where('qr_token', $qr_token)
            ->where('is_active', true)
            ->first();

        if (! $table) {
            return view('customer.invalid-qr');
        }

        $request->session()->put('qr_table_id', $table->id);
        $request->session()->put('qr_table_number', $table->number);
        $request->session()->put('qr_table_label', $table->label);
        $request->session()->put('qr_token', $table->qr_token);

        if (! $request->session()->has('ordering_mode')) {
            $request->session()->put('ordering_mode', auth()->check() ? 'member' : 'guest');
        }

        return redirect()->route('menu.index')
            ->with('toast_success', "QR Meja Terverifikasi • {$table->label}. Silakan pilih menu pesanan Anda.");
    }

    public function continueAsGuest(Request $request, string $qr_token): RedirectResponse
    {
        $table = DiningTable::query()
            ->where('qr_token', $qr_token)
            ->where('is_active', true)
            ->first();

        if (! $table) {
            return redirect()->route('table.scan', ['qr_token' => $qr_token]);
        }

        $request->session()->put('qr_table_id', $table->id);
        $request->session()->put('qr_table_number', $table->number);
        $request->session()->put('qr_table_label', $table->label);
        $request->session()->put('qr_token', $table->qr_token);
        $request->session()->put('ordering_mode', 'guest');

        return redirect()->route('menu.index');
    }

    /**
     * A2 Menu Homepage & A3 Category Filter: /menu
     */
    public function menu(Request $request): View
    {
        $table = $this->resolveSessionTable($request);

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $selectedCategory = $request->query('category');
        $search = trim((string) $request->query('q', ''));

        $productsQuery = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true));

        if (! empty($selectedCategory)) {
            $productsQuery->whereHas('category', function ($q) use ($selectedCategory) {
                $q->where('slug', $selectedCategory)->orWhere('id', $selectedCategory);
            });
        }

        if ($search !== '') {
            $productsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $productsQuery
            ->orderByDesc('is_available')
            ->orderBy('name')
            ->get();

        $cartSummary = $this->getCartSummary($request);

        return view('customer.menu', [
            'table' => $table,
            'categories' => $categories,
            'products' => $products,
            'selectedCategory' => $selectedCategory,
            'search' => $search,
            'cartSummary' => $cartSummary,
        ]);
    }

    /**
     * A4 Product Detail: /menu/{product:slug}
     */
    public function productDetail(Request $request, Product $product): View
    {
        abort_unless($product->is_active, 404);
        $table = $this->resolveSessionTable($request);
        $cartSummary = $this->getCartSummary($request);

        return view('customer.product-detail', [
            'table' => $table,
            'product' => $product->load('category'),
            'cartSummary' => $cartSummary,
        ]);
    }

    /**
     * A5 Cart Page: /cart
     */
    public function cart(Request $request): View
    {
        $table = $this->resolveSessionTable($request);
        $cartData = $this->getCartSummary($request);

        return view('customer.cart', [
            'table' => $table,
            'cartData' => $cartData,
        ]);
    }

    public function addToCart(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'note' => ['nullable', 'string', 'max:200'],
        ], [
            'quantity.min' => 'Jumlah minimal 1.',
            'quantity.max' => 'Jumlah maksimal 99.',
            'note.max' => 'Catatan maksimal 200 karakter.',
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);
        if (! $product->is_active || ! $product->is_available) {
            return back()->with('toast_error', "Maaf, {$product->name} sedang habis atau tidak tersedia.");
        }

        $cart = (array) $request->session()->get('customer_cart', []);
        $note = isset($validated['note']) ? trim(strip_tags((string) $validated['note'])) : '';
        $lineKey = $product->id.'_'.md5(strtolower($note));

        if (isset($cart[$lineKey])) {
            $cart[$lineKey]['quantity'] = min(99, (int) $cart[$lineKey]['quantity'] + (int) $validated['quantity']);
        } else {
            $cart[$lineKey] = [
                'line_key' => $lineKey,
                'product_id' => $product->id,
                'quantity' => (int) $validated['quantity'],
                'note' => $note ?: null,
            ];
        }

        $request->session()->put('customer_cart', $cart);

        return back()->with('toast_success', "{$product->name} ditambahkan ke keranjang.");
    }

    public function updateCartItem(Request $request, string $lineKey): RedirectResponse
    {
        $cart = (array) $request->session()->get('customer_cart', []);
        if (! isset($cart[$lineKey])) {
            return redirect()->route('cart.index');
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $qty = (int) $validated['quantity'];
        if ($qty <= 0) {
            unset($cart[$lineKey]);
            $request->session()->put('customer_cart', $cart);

            return redirect()->route('cart.index')->with('toast_info', 'Item dihapus dari keranjang.');
        }

        $cart[$lineKey]['quantity'] = $qty;
        if ($request->has('note')) {
            $cart[$lineKey]['note'] = trim(strip_tags((string) $validated['note'])) ?: null;
        }

        $request->session()->put('customer_cart', $cart);

        return redirect()->route('cart.index')->with('toast_success', 'Keranjang diperbarui.');
    }

    public function removeCartItem(Request $request, string $lineKey): RedirectResponse
    {
        $cart = (array) $request->session()->get('customer_cart', []);
        unset($cart[$lineKey]);
        $request->session()->put('customer_cart', $cart);

        return redirect()->route('cart.index')->with('toast_info', 'Item dihapus dari keranjang.');
    }

    /**
     * A6 Checkout Page: /checkout (Step 4 of 5)
     */
    public function checkout(Request $request): View|RedirectResponse
    {
        $table = $this->resolveSessionTable($request);
        if (! $table) {
            return redirect()->route('menu.index')->with('toast_error', 'Silakan pindai QR meja terlebih dahulu untuk melanjutkan checkout.');
        }

        $cartData = $this->getCartSummary($request);
        if (empty($cartData['lines'])) {
            return redirect()->route('menu.index')->with('toast_error', 'Keranjang pesanan masih kosong.');
        }

        if ($cartData['has_unavailable']) {
            return redirect()->route('cart.index')->with('toast_error', 'Terdapat menu yang baru saja habis di keranjang Anda. Silakan hapus item tersebut terlebih dahulu.');
        }

        $member = Auth::user()?->member;
        $eligiblePromos = $member ? $this->promotionEngine->eligiblePromotions($member, $cartData['subtotal'], now()) : collect();
        $activeVouchers = $member
            ? $member->rewardRedemptions()->with('reward')->where('status', 'active')->get()->filter->isUsable()
            : collect();

        $selectedPromoId = $request->query('promotion_id') ? (int) $request->query('promotion_id') : null;
        $selectedVoucherId = $request->query('reward_redemption_id') ? (int) $request->query('reward_redemption_id') : null;

        // Compute live preview with chosen promo/voucher
        try {
            $pricing = $this->pricingService->calculate(
                array_values($cartData['raw_lines']),
                $member,
                $selectedPromoId,
                $selectedVoucherId,
                false
            );
        } catch (ValidationException) {
            $selectedPromoId = null;
            $selectedVoucherId = null;
            $pricing = $this->pricingService->calculate(array_values($cartData['raw_lines']), $member, null, null, false);
        }

        if (! $request->session()->has('checkout_client_request_id')) {
            $request->session()->put('checkout_client_request_id', (string) Str::uuid());
        }

        return view('customer.checkout', [
            'table' => $table,
            'cartData' => $cartData,
            'pricing' => $pricing,
            'member' => $member,
            'eligiblePromos' => $eligiblePromos,
            'activeVouchers' => $activeVouchers,
            'selectedPromoId' => $selectedPromoId,
            'selectedVoucherId' => $selectedVoucherId,
            'clientRequestId' => $request->session()->get('checkout_client_request_id'),
        ]);
    }

    /**
     * POST /checkout — Creates Order (status Menunggu Pembayaran) idempotently.
     */
    public function placeOrder(Request $request): RedirectResponse
    {
        $table = $this->resolveSessionTable($request);
        if (! $table) {
            return redirect()->route('menu.index')->withErrors([
                'table' => 'QR meja tidak valid atau belum dipilih.',
            ]);
        }

        $rawCart = (array) $request->session()->get('customer_cart', []);
        if (empty($rawCart)) {
            return redirect()->route('menu.index')->with('toast_error', 'Keranjang Anda kosong.');
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'min:2', 'max:60'],
            'customer_email' => ['required', 'email', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'promotion_id' => ['nullable', 'integer'],
            'reward_redemption_id' => ['nullable', 'integer'],
            'payment_method' => ['nullable', 'in:tunai,qris'],
            'client_request_id' => ['required', 'string', 'max:64'],
        ], [
            'customer_name.required' => 'Nama pemesan wajib diisi.',
            'customer_name.min' => 'Nama pemesan minimal 2 karakter.',
            'customer_email.required' => 'Alamat email wajib diisi untuk pengiriman invoice digital.',
            'customer_email.email' => 'Format alamat email tidak valid.',
        ]);

        $member = Auth::user()?->member;

        $order = $this->orderService->create([
            'client_request_id' => $validated['client_request_id'],
            'source' => 'qr',
            'table_id' => $table->id,
            'member_id' => $member?->id,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'] ?? ($member?->user?->phone),
            'items' => array_values($rawCart),
            'promotion_id' => ! empty($validated['promotion_id']) ? (int) $validated['promotion_id'] : null,
            'reward_redemption_id' => ! empty($validated['reward_redemption_id']) ? (int) $validated['reward_redemption_id'] : null,
            'payment_method' => $validated['payment_method'] ?? null,
        ]);

        // Track ownership in Guest/Member session
        $tracked = (array) $request->session()->get('tracked_order_tokens', []);
        if (! in_array($order->public_token, $tracked, true)) {
            $tracked[] = $order->public_token;
            $request->session()->put('tracked_order_tokens', $tracked);
        }
        $request->session()->put('current_order_token', $order->public_token);

        // Clear cart & reset idempotency token
        $request->session()->forget(['customer_cart', 'checkout_client_request_id']);

        return redirect()->route('order.payment', ['public_token' => $order->public_token])
            ->with('toast_success', "Pesanan {$order->order_number} berhasil dibuat. Silakan selesaikan pembayaran.");
    }

    /**
     * A7 Payment Selection & A8 Payment Status: /order/{public_token}/payment
     */
    public function paymentPage(Request $request, string $public_token): View|RedirectResponse
    {
        $order = $this->findAuthorizedOrderOr404($request, $public_token);

        if ($order->status->isPaidOrLater()) {
            return redirect()->route('order.track', ['public_token' => $order->public_token]);
        }

        // Expire stale QRIS if past window
        $this->paymentService->expireStaleQris();
        $order->load(['items', 'table', 'payments']);

        $latestPayment = $order->payments->first();
        $qrisSvg = null;

        if ($latestPayment && $latestPayment->method === PaymentMethod::Qris && $latestPayment->qris_payload) {
            $options = new QROptions([
                'outputBase64' => false,
                'addQuietzone' => true,
            ]);
            $qrisSvg = (new QRCode($options))->render($latestPayment->qris_payload);
        }

        return view('customer.payment', [
            'order' => $order,
            'latestPayment' => $latestPayment,
            'qrisSvg' => $qrisSvg,
        ]);
    }

    public function selectPaymentMethod(Request $request, string $public_token): RedirectResponse
    {
        $order = $this->findAuthorizedOrderOr404($request, $public_token);

        $validated = $request->validate([
            'payment_method' => ['required', 'in:tunai,qris'],
        ], [
            'payment_method.required' => 'Silakan pilih metode pembayaran (Tunai atau QRIS).',
            'payment_method.in' => 'Metode pembayaran hanya mendukung Tunai atau QRIS.',
        ]);

        $method = PaymentMethod::from($validated['payment_method']);
        $this->paymentService->selectPaymentMethod($order, $method);

        $label = $method === PaymentMethod::Qris ? 'QRIS' : 'Tunai di Kasir';

        return redirect()->route('order.payment', ['public_token' => $order->public_token])
            ->with('toast_success', "Metode pembayaran {$label} dipilih.");
    }

    /**
     * A9 Order Tracking & A10 Order Success: /order/{public_token}
     */
    public function trackOrder(Request $request, string $public_token): View
    {
        $order = $this->findAuthorizedOrderOr404($request, $public_token);
        $order->load(['items', 'table', 'payments', 'invoice.latestDelivery', 'statusHistories', 'promotion']);

        return view('customer.order-tracking', [
            'order' => $order,
        ]);
    }

    /**
     * JSON status polling endpoint (every 3–5s)
     */
    public function orderStatusJson(Request $request, string $public_token): JsonResponse
    {
        $this->paymentService->expireStaleQris();
        $order = $this->findAuthorizedOrderOr404($request, $public_token);
        $latestPayment = $order->latestPayment;

        return response()->json([
            'order_number' => $order->order_number,
            'order_code' => $order->order_code,
            'status' => $order->status->value,
            'is_paid' => $order->status->isPaidOrLater(),
            'payment_method' => $order->payment_method?->value,
            'payment_status' => $latestPayment?->status?->value,
            'expires_at' => $latestPayment?->expires_at?->toIso8601String(),
            'has_invoice' => $order->invoice()->exists(),
            'track_url' => route('order.track', ['public_token' => $order->public_token]),
        ]);
    }

    /**
     * Customer cancels own order while Menunggu Pembayaran (D10)
     */
    public function cancelOwnOrder(Request $request, string $public_token): RedirectResponse
    {
        $order = $this->findAuthorizedOrderOr404($request, $public_token);

        if ($order->status !== OrderStatus::MenungguPembayaran) {
            return back()->with('toast_error', 'Pesanan yang sudah dibayar tidak dapat dibatalkan sendiri. Silakan hubungi staf.');
        }

        $this->orderService->cancel($order, Auth::user(), 'Dibatalkan oleh pelanggan sebelum pembayaran', 'customer');

        return redirect()->route('order.track', ['public_token' => $order->public_token])
            ->with('toast_info', "Pesanan {$order->order_number} telah dibatalkan.");
    }

    /**
     * G HTML Digital Invoice View: /order/{public_token}/invoice
     */
    public function showInvoice(Request $request, string $public_token): View
    {
        $order = $this->findAuthorizedOrderOr404($request, $public_token);
        $invoice = $order->invoice()->with('deliveries')->firstOrFail();

        return view('customer.invoice', [
            'order' => $order->load(['items', 'table']),
            'invoice' => $invoice,
            'snapshot' => $invoice->snapshot ?? [],
        ]);
    }

    /**
     * G PDF Digital Invoice Download: /order/{public_token}/invoice.pdf
     */
    public function downloadInvoicePdf(Request $request, string $public_token): Response
    {
        $order = $this->findAuthorizedOrderOr404($request, $public_token);
        $invoice = $order->invoice()->firstOrFail();

        $binary = $this->invoiceService->renderPdfBinary($invoice);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$invoice->invoice_number.'.pdf"',
        ]);
    }

    /**
     * A16 Public & Member Promotion Page: /promotions (D13)
     */
    public function promotions(Request $request): View
    {
        $table = $this->resolveSessionTable($request);
        $promotions = Promotion::query()
            ->where('is_active', true)
            ->where('rule_type', '!=', PromotionRuleType::ReturnTracking->value)
            ->orderBy('id')
            ->get();

        $member = Auth::user()?->member;
        $cartData = $this->getCartSummary($request);
        $eligibleIds = $member
            ? $this->promotionEngine->eligiblePromotions($member, max(50000, $cartData['subtotal']), now())->pluck('id')->all()
            : [];

        return view('customer.promotions', [
            'table' => $table,
            'promotions' => $promotions,
            'member' => $member,
            'eligibleIds' => $eligibleIds,
            'cartSummary' => $cartData,
        ]);
    }

    protected function resolveSessionTable(Request $request): ?DiningTable
    {
        $tableId = $request->session()->get('qr_table_id');
        if (! $tableId) {
            return null;
        }

        $table = DiningTable::query()->whereKey($tableId)->where('is_active', true)->first();
        if (! $table || $table->qr_token !== $request->session()->get('qr_token')) {
            $request->session()->forget(['qr_table_id', 'qr_table_number', 'qr_table_label', 'qr_token']);

            return null;
        }

        return $table;
    }

    protected function getCartSummary(Request $request): array
    {
        $rawCart = (array) $request->session()->get('customer_cart', []);
        if (empty($rawCart)) {
            return [
                'raw_lines' => [],
                'lines' => [],
                'item_count' => 0,
                'subtotal' => 0,
                'tax_rate' => (float) Setting::getValue('tax_rate', 0),
                'tax_amount' => 0,
                'total_amount' => 0,
                'has_unavailable' => false,
            ];
        }

        $productIds = collect($rawCart)->pluck('product_id')->unique()->all();
        $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');

        $lines = [];
        $itemCount = 0;
        $subtotal = 0;
        $hasUnavailable = false;

        foreach ($rawCart as $key => $row) {
            $product = $products->get((int) ($row['product_id'] ?? 0));
            if (! $product || ! $product->is_active) {
                $hasUnavailable = true;
                continue;
            }

            $available = (bool) $product->is_available;
            if (! $available) {
                $hasUnavailable = true;
            }

            $qty = max(1, (int) ($row['quantity'] ?? 1));
            $lineTotal = (int) $product->price * $qty;

            if ($available) {
                $itemCount += $qty;
                $subtotal += $lineTotal;
            }

            $lines[] = [
                'line_key' => $key,
                'product' => $product,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => (int) $product->price,
                'quantity' => $qty,
                'note' => $row['note'] ?? null,
                'line_total' => $lineTotal,
                'is_available' => $available,
            ];
        }

        $taxRate = max(0.0, (float) Setting::getValue('tax_rate', 0));
        $taxAmount = $taxRate > 0 ? (int) round($subtotal * ($taxRate / 100)) : 0;

        return [
            'raw_lines' => $rawCart,
            'lines' => $lines,
            'item_count' => $itemCount,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount,
            'has_unavailable' => $hasUnavailable,
        ];
    }

    /**
     * Section 5.3: Every order/invoice lookup is scoped by ownership.
     * Returns 404 (not 403) for other customers' resources to avoid enumeration.
     */
    protected function findAuthorizedOrderOr404(Request $request, string $publicToken): Order
    {
        $order = Order::query()->where('public_token', $publicToken)->firstOrFail();

        $user = Auth::user();
        if ($user && $user->can('orders.view')) {
            return $order;
        }

        // If the order belongs to a registered Member, only that Member can access it
        if ($order->member_id !== null) {
            if ($user && $user->member && $user->member->id === $order->member_id) {
                return $order;
            }
            abort(404);
        }

        // For Guest orders (member_id is null): if a different Member is logged in and doesn't have the token in session, 404
        $trackedTokens = (array) $request->session()->get('tracked_order_tokens', []);
        if ($user && $user->hasRole('member') && ! in_array($publicToken, $trackedTokens, true)) {
            abort(404);
        }

        return $order;
    }
}
