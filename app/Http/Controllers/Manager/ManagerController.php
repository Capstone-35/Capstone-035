<?php

namespace App\Http\Controllers\Manager;

use App\Domain\Loyalty\LoyaltyService;
use App\Domain\Ordering\OrderService;
use App\Domain\Rfm\RfmService;
use App\Domain\Reporting\ReportService;
use App\Enums\OrderStatus;
use App\Enums\PromotionRuleType;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Member;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Reward;
use App\Models\RfmAnalysis;
use App\Models\RfmResult;
use App\Models\RfmSegment;
use App\Models\Setting;
use App\Models\User;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManagerController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected RfmService $rfmService,
        protected OrderService $orderService,
        protected LoyaltyService $loyaltyService
    ) {}

    /**
     * K1 Manager Dashboard: /manager
     */
    public function dashboard(Request $request): View
    {
        $period = (string) $request->query('period', 'bulanan');
        $dateFrom = $request->query('from');
        $dateTo = $request->query('to');

        $resolved = $this->reportService->resolvePeriod($period, $dateFrom, $dateTo);
        $summary = $this->reportService->getSummary($resolved['start'], $resolved['end']);
        $breakdown = $this->reportService->getPeriodBreakdown($resolved['start'], $resolved['end'], $resolved['group']);
        $topProducts = $this->reportService->getTopProducts($resolved['start'], $resolved['end'], 8);
        $categorySales = $this->reportService->getSalesByCategory($resolved['start'], $resolved['end']);
        $rfmDistribution = $this->reportService->getRfmDistribution();
        $promoPerformance = array_slice($this->reportService->getPromotionPerformance(), 0, 6);

        $recentOrders = Order::query()
            ->with(['table', 'member.user', 'invoice.latestDelivery'])
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('manager.dashboard', [
            'period' => $period,
            'dateFrom' => $resolved['start']->toDateString(),
            'dateTo' => $resolved['end']->toDateString(),
            'periodLabel' => $resolved['label'],
            'summary' => $summary,
            'breakdown' => $breakdown,
            'topProducts' => $topProducts,
            'categorySales' => $categorySales,
            'rfmDistribution' => $rfmDistribution,
            'promoPerformance' => $promoPerformance,
            'recentOrders' => $recentOrders,
        ]);
    }

    /**
     * Transactions Explorer: /manager/transactions
     */
    public function transactions(Request $request): View
    {
        $status = $request->query('status');
        $method = $request->query('payment_method');
        $source = $request->query('source');
        $search = trim((string) $request->query('q', ''));
        $dateFrom = $request->query('from');
        $dateTo = $request->query('to');

        $query = Order::query()
            ->with(['table', 'member.user', 'invoice.latestDelivery', 'promotion'])
            ->latest('created_at');

        if (! empty($status)) {
            $query->where('status', $status);
        }
        if (! empty($method)) {
            $query->where('payment_method', $method);
        }
        if (! empty($source)) {
            $query->where('source', $source);
        }
        if (! empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if (! empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
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

        return view('manager.transactions', [
            'orders' => $orders,
            'status' => $status,
            'method' => $method,
            'source' => $source,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function showTransaction(Order $order): View
    {
        $order->load([
            'table',
            'items.product',
            'member.user',
            'payments.confirmer',
            'invoice.deliveries',
            'statusHistories.actor',
            'promotion',
            'rewardRedemption.reward',
            'pointLedgers',
        ]);

        return view('manager.transaction-detail', [
            'order' => $order,
        ]);
    }

    /**
     * D10 Manager Cancellation (including post-payment Dibayar / Diproses with mandatory reason & out-of-system refund notice)
     */
    public function cancelOrder(Request $request, Order $order): RedirectResponse
    {
        $wasPaid = in_array($order->status, [OrderStatus::Dibayar, OrderStatus::Diproses], true);

        $validated = $request->validate([
            'reason' => [$wasPaid ? 'required' : 'nullable', 'string', 'min:3', 'max:255'],
        ], [
            'reason.required' => 'Alasan pembatalan wajib diisi untuk pesanan yang sudah dibayar.',
            'reason.min' => 'Alasan pembatalan minimal 3 karakter.',
        ]);

        try {
            $this->orderService->cancel(
                $order,
                Auth::user(),
                $validated['reason'] ?: 'Dibatalkan oleh Manager',
                'manager'
            );

            $notice = $wasPaid
                ? "Pesanan {$order->order_number} dibatalkan oleh Manager. Poin & voucher telah dikembalikan. Catatan: Pengembalian dana ditangani di luar sistem."
                : "Pesanan {$order->order_number} berhasil dibatalkan.";

            return back()->with('toast_info', $notice);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('toast_error', collect($e->errors())->flatten()->first());
        }
    }

    /**
     * Live Operational Orders Monitor: /manager/orders
     */
    public function orders(): View
    {
        $activeOrders = Order::query()
            ->with(['table', 'items', 'member.user', 'latestPayment'])
            ->whereIn('status', [
                OrderStatus::MenungguPembayaran->value,
                OrderStatus::Dibayar->value,
                OrderStatus::Diproses->value,
                OrderStatus::Siap->value,
            ])
            ->latest('created_at')
            ->get();

        return view('manager.orders', [
            'activeOrders' => $activeOrders,
        ]);
    }

    /**
     * Member Directory & Detail: /manager/members
     */
    public function members(Request $request): View
    {
        $segmentFilter = $request->query('segment');
        $search = trim((string) $request->query('q', ''));

        $query = Member::query()
            ->with(['user', 'latestRfmResult.segment'])
            ->withCount(['orders as successful_orders_count' => fn ($q) => $q->successful()])
            ->withSum(['orders as total_spent' => fn ($q) => $q->successful()], 'total_amount')
            ->orderByDesc('points_balance');

        if (! empty($segmentFilter)) {
            $query->whereHas('latestRfmResult', function ($q) use ($segmentFilter) {
                $q->where('segment_name', $segmentFilter);
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('member_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $members = $query->paginate(20)->withQueryString();
        $segments = RfmSegment::query()->orderBy('priority')->get();

        return view('manager.members', [
            'members' => $members,
            'segments' => $segments,
            'segmentFilter' => $segmentFilter,
            'search' => $search,
        ]);
    }

    public function showMember(Member $member): View
    {
        $member->load([
            'user',
            'latestRfmResult.segment',
            'rfmResults.analysis',
            'pointLedger.order',
            'rewardRedemptions.reward',
            'promotionUsages.promotion',
            'promotionUsages.returnRecord',
        ]);

        $orders = $member->orders()
            ->with(['table', 'items', 'invoice'])
            ->latest('created_at')
            ->paginate(15);

        return view('manager.member-detail', [
            'member' => $member,
            'orders' => $orders,
        ]);
    }

    /**
     * Module I — RFM Analysis Engine: /manager/rfm (FR-12, NFR-17)
     */
    public function rfmIndex(Request $request): View
    {
        $latestAnalysis = RfmAnalysis::query()->latest('id')->first();
        if (! $latestAnalysis) {
            $latestAnalysis = $this->rfmService->runAnalysis(now(), null, Auth::user()?->name ?? 'manager');
        }

        $segments = RfmSegment::query()->orderBy('priority')->get();
        $distribution = $this->reportService->getRfmDistribution();
        $parameters = $this->rfmService->getParameters();

        $segmentFilter = $request->query('segment');
        $resultsQuery = RfmResult::query()
            ->with(['member.user', 'segment'])
            ->where('rfm_analysis_id', $latestAnalysis->id)
            ->orderByDesc('r_score')
            ->orderByDesc('f_score')
            ->orderByDesc('m_score');

        if (! empty($segmentFilter)) {
            $resultsQuery->where('segment_name', $segmentFilter);
        }

        $results = $resultsQuery->paginate(20)->withQueryString();

        return view('manager.rfm', [
            'latestAnalysis' => $latestAnalysis,
            'segments' => $segments,
            'distribution' => $distribution,
            'parameters' => $parameters,
            'results' => $results,
            'segmentFilter' => $segmentFilter,
        ]);
    }

    public function rfmRecalculate(): RedirectResponse
    {
        $analysis = $this->rfmService->runAnalysis(now(), null, Auth::user()?->name ?? 'manager');

        return redirect()->route('manager.rfm.index')
            ->with('toast_success', "Skor dan segmen RFM berhasil dihitung ulang untuk {$analysis->members_evaluated} Member.");
    }

    public function rfmSegment(RfmSegment $segment): View
    {
        $latestAnalysis = RfmAnalysis::query()->latest('id')->first();

        $results = RfmResult::query()
            ->with(['member.user'])
            ->when($latestAnalysis, fn ($q) => $q->where('rfm_analysis_id', $latestAnalysis->id))
            ->where('segment_name', $segment->name)
            ->orderByDesc('monetary')
            ->paginate(25);

        $targetedPromos = Promotion::query()
            ->where('is_active', true)
            ->get()
            ->filter(function (Promotion $promo) use ($segment) {
                $targets = (array) ($promo->target_segments ?? []);

                return in_array('all', $targets, true)
                    || in_array($segment->slug, $targets, true)
                    || in_array($segment->name, $targets, true);
            });

        return view('manager.rfm-segment', [
            'segment' => $segment,
            'latestAnalysis' => $latestAnalysis,
            'results' => $results,
            'targetedPromos' => $targetedPromos,
        ]);
    }

    public function rfmParameters(): View
    {
        return view('manager.rfm-parameters', [
            'parameters' => $this->rfmService->getParameters(),
            'segments' => RfmSegment::query()->orderBy('priority')->get(),
        ]);
    }

    public function rfmUpdateParameters(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'period_days' => ['required', 'integer', 'min:7', 'max:730'],
            'r_thresholds' => ['required', 'array'],
            'r_thresholds.5' => ['required', 'integer', 'min:1'],
            'r_thresholds.4' => ['required', 'integer', 'min:2'],
            'r_thresholds.3' => ['required', 'integer', 'min:3'],
            'r_thresholds.2' => ['required', 'integer', 'min:4'],
            'f_thresholds' => ['required', 'array'],
            'f_thresholds.5' => ['required', 'integer', 'min:4'],
            'f_thresholds.4' => ['required', 'integer', 'min:3'],
            'f_thresholds.3' => ['required', 'integer', 'min:2'],
            'f_thresholds.2' => ['required', 'integer', 'min:1'],
            'm_thresholds' => ['required', 'array'],
            'm_thresholds.5' => ['required', 'integer', 'min:1000'],
            'm_thresholds.4' => ['required', 'integer', 'min:1000'],
            'm_thresholds.3' => ['required', 'integer', 'min:1000'],
            'm_thresholds.2' => ['required', 'integer', 'min:1000'],
        ]);

        try {
            $this->rfmService->updateParameters($validated, Auth::id());
            $this->rfmService->runAnalysis(now(), null, Auth::user()?->name ?? 'manager');

            return redirect()->route('manager.rfm.parameters')
                ->with('toast_success', 'Parameter skor RFM berhasil disimpan dan analisis RFM telah diperbarui.');
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors())->with('toast_error', collect($e->errors())->flatten()->first());
        }
    }

    /**
     * Module J — Behavioral Promotion Engine Management: /manager/promotions (FR-10, FR-12, UC-09, D1)
     */
    public function promotionsIndex(): View
    {
        $promotions = Promotion::query()
            ->withCount('usages')
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->get();

        return view('manager.promotions', [
            'promotions' => $promotions,
            'ruleTypes' => PromotionRuleType::cases(),
        ]);
    }

    public function promotionsCreate(): View
    {
        return view('manager.promotion-form', [
            'promotion' => new Promotion([
                'rule_type' => PromotionRuleType::Comeback,
                'benefit_type' => 'percent',
                'benefit_value' => 15,
                'min_transaction' => 30000,
                'per_member_limit' => 1,
                'is_active' => true,
                'starts_at' => now()->startOfDay(),
                'ends_at' => now()->addDays(30)->endOfDay(),
                'target_segments' => ['all'],
                'conditions' => [
                    'inactive_days' => 30,
                    'required_orders' => 5,
                    'window_days' => 30,
                    'welcome_days' => 14,
                    'days_of_week' => [1, 2, 3, 4, 5],
                    'start_time' => '14:00',
                    'end_time' => '17:00',
                    'holiday_name' => 'Hari Kemerdekaan / Hari Besar',
                ],
            ]),
            'ruleTypes' => PromotionRuleType::cases(),
            'segments' => RfmSegment::query()->orderBy('priority')->get(),
            'isEdit' => false,
        ]);
    }

    public function promotionsStore(Request $request): RedirectResponse
    {
        $validated = $this->validatePromotionPayload($request);

        $promotion = Promotion::query()->create([
            ...$validated,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('manager.promotions.show', $promotion)
            ->with('toast_success', "Promosi '{$promotion->name}' berhasil ditambahkan.");
    }

    public function promotionsShow(Promotion $promotion): View
    {
        $promotion->load(['usages.member.user', 'usages.order', 'usages.returnRecord.returnedOrder']);

        return view('manager.promotion-detail', [
            'promotion' => $promotion,
        ]);
    }

    public function promotionsEdit(Promotion $promotion): View
    {
        return view('manager.promotion-form', [
            'promotion' => $promotion,
            'ruleTypes' => PromotionRuleType::cases(),
            'segments' => RfmSegment::query()->orderBy('priority')->get(),
            'isEdit' => true,
        ]);
    }

    public function promotionsUpdate(Request $request, Promotion $promotion): RedirectResponse
    {
        $validated = $this->validatePromotionPayload($request, $promotion->id);

        $promotion->update([
            ...$validated,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('manager.promotions.show', $promotion)
            ->with('toast_success', "Promosi '{$promotion->name}' berhasil diperbarui.");
    }

    public function promotionsToggle(Promotion $promotion): RedirectResponse
    {
        $promotion->update([
            'is_active' => ! $promotion->is_active,
            'updated_by' => Auth::id(),
        ]);

        $statusLabel = $promotion->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('toast_success', "Promosi '{$promotion->name}' berhasil {$statusLabel}.");
    }

    public function promotionsPerformance(): View
    {
        $performance = $this->reportService->getPromotionPerformance();

        return view('manager.promotion-performance', [
            'performance' => $performance,
        ]);
    }

    /**
     * Module H — Loyalty Settings & Rewards Catalog: /manager/loyalty & /manager/rewards
     */
    public function loyaltyIndex(): View
    {
        return view('manager.loyalty', [
            'pointsPerAmount' => (int) Setting::getValue('loyalty.points_per_amount', 10000),
            'earnOnStatus' => (string) Setting::getValue('loyalty.earn_on_status', OrderStatus::Selesai->value),
            'taxRate' => (float) Setting::getValue('tax_rate', 0),
            'qrisExpiryMinutes' => (int) Setting::getValue('qris_expiry_minutes', 15),
            'rewards' => Reward::query()->orderBy('points_cost')->get(),
        ]);
    }

    public function loyaltyUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'points_per_amount' => ['required', 'integer', 'min:1000', 'max:1000000'],
            'earn_on_status' => ['required', Rule::in([OrderStatus::Selesai->value, OrderStatus::Dibayar->value])],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:30'],
            'qris_expiry_minutes' => ['required', 'integer', 'min:1', 'max:120'],
        ]);

        Setting::setValue('loyalty.points_per_amount', (int) $validated['points_per_amount'], 'int', 'Kelipatan Rupiah untuk 1 poin loyalti', Auth::id());
        Setting::setValue('loyalty.earn_on_status', $validated['earn_on_status'], 'string', 'Status transaksi pemicu perolehan poin & transaksi berhasil', Auth::id());
        Setting::setValue('tax_rate', (float) $validated['tax_rate'], 'float', 'Persentase pajak checkout & invoice (D2)', Auth::id());
        Setting::setValue('qris_expiry_minutes', (int) $validated['qris_expiry_minutes'], 'int', 'Batas waktu kedaluwarsa QRIS (menit)', Auth::id());

        return back()->with('toast_success', 'Pengaturan Loyalti, Pajak, dan QRIS berhasil diperbarui.');
    }

    public function rewardsIndex(): View
    {
        $rewards = Reward::query()
            ->withCount('redemptions')
            ->orderBy('points_cost')
            ->get();

        return view('manager.rewards', [
            'rewards' => $rewards,
        ]);
    }

    public function rewardsStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'points_cost' => ['required', 'integer', 'min:1'],
            'benefit_type' => ['required', 'in:nominal,percent'],
            'benefit_value' => ['required', 'integer', 'min:1'],
            'max_discount' => ['nullable', 'integer', 'min:0'],
            'valid_days' => ['required', 'integer', 'min:1', 'max:365'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validated['benefit_type'] === 'percent' && $validated['benefit_value'] > 100) {
            return back()->withInput()->withErrors(['benefit_value' => 'Persentase diskon maksimal 100%.']);
        }

        Reward::query()->create([
            'name' => trim(strip_tags($validated['name'])),
            'description' => $validated['description'] ?? null,
            'points_cost' => (int) $validated['points_cost'],
            'benefit_type' => $validated['benefit_type'],
            'benefit_value' => (int) $validated['benefit_value'],
            'max_discount' => ! empty($validated['max_discount']) ? (int) $validated['max_discount'] : null,
            'valid_days' => (int) $validated['valid_days'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('toast_success', 'Katalog reward berhasil ditambahkan.');
    }

    public function rewardsUpdate(Request $request, Reward $reward): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'points_cost' => ['required', 'integer', 'min:1'],
            'benefit_type' => ['required', 'in:nominal,percent'],
            'benefit_value' => ['required', 'integer', 'min:1'],
            'max_discount' => ['nullable', 'integer', 'min:0'],
            'valid_days' => ['required', 'integer', 'min:1', 'max:365'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $reward->update([
            'name' => trim(strip_tags($validated['name'])),
            'description' => $validated['description'] ?? null,
            'points_cost' => (int) $validated['points_cost'],
            'benefit_type' => $validated['benefit_type'],
            'benefit_value' => (int) $validated['benefit_value'],
            'max_discount' => ! empty($validated['max_discount']) ? (int) $validated['max_discount'] : null,
            'valid_days' => (int) $validated['valid_days'],
            'is_active' => $request->boolean('is_active', false),
        ]);

        return back()->with('toast_success', "Reward '{$reward->name}' berhasil diperbarui.");
    }

    /**
     * Product & Category Management: /manager/products & /manager/categories
     */
    public function productsIndex(Request $request): View
    {
        $categoryId = $request->query('category_id');
        $search = trim((string) $request->query('q', ''));

        $query = Product::query()
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('name');

        if (! empty($categoryId)) {
            $query->where('category_id', $categoryId);
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return view('manager.products', [
            'products' => $query->paginate(20)->withQueryString(),
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'categoryId' => $categoryId,
            'search' => $search,
        ]);
    }

    public function productsCreate(): View
    {
        return view('manager.product-form', [
            'product' => new Product([
                'is_available' => true,
                'is_active' => true,
                'price' => 25000,
            ]),
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'isEdit' => false,
        ]);
    }

    public function productsStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:300'],
            'price' => ['required', 'integer', 'min:1000', 'max:5000000'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_available' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($validated['name']).'-'.strtolower(Str::random(4));

        Product::query()->create([
            'category_id' => (int) $validated['category_id'],
            'name' => trim(strip_tags($validated['name'])),
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'price' => (int) $validated['price'],
            'image_path' => $validated['image_path'] ?: '/images/products/default-coffee.svg',
            'is_available' => $request->boolean('is_available', true),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('manager.products.index')
            ->with('toast_success', 'Menu baru berhasil ditambahkan.');
    }

    public function productsEdit(Product $product): View
    {
        return view('manager.product-form', [
            'product' => $product,
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'isEdit' => true,
        ]);
    }

    public function productsUpdate(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:300'],
            'price' => ['required', 'integer', 'min:1000', 'max:5000000'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_available' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product->update([
            'category_id' => (int) $validated['category_id'],
            'name' => trim(strip_tags($validated['name'])),
            'description' => $validated['description'] ?? null,
            'price' => (int) $validated['price'],
            'image_path' => $validated['image_path'] ?: $product->image_path,
            'is_available' => $request->boolean('is_available', false),
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()->route('manager.products.index')
            ->with('toast_success', "Menu '{$product->name}' berhasil diperbarui.");
    }

    public function productsDestroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('manager.products.index')
            ->with('toast_info', "Menu '{$product->name}' telah diarsipkan.");
    }

    public function categoriesIndex(): View
    {
        $categories = Category::query()
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();

        return view('manager.categories', [
            'categories' => $categories,
        ]);
    }

    public function categoriesStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Category::query()->create([
            'name' => trim(strip_tags($validated['name'])),
            'slug' => Str::slug($validated['name']).'-'.strtolower(Str::random(3)),
            'sort_order' => (int) $validated['sort_order'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('toast_success', 'Kategori menu berhasil ditambahkan.');
    }

    public function categoriesUpdate(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => trim(strip_tags($validated['name'])),
            'sort_order' => (int) $validated['sort_order'],
            'is_active' => $request->boolean('is_active', false),
        ]);

        return back()->with('toast_success', "Kategori '{$category->name}' berhasil diperbarui.");
    }

    public function categoriesDestroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('toast_error', 'Kategori masih memiliki produk aktif. Pindahkan produk terlebih dahulu.');
        }

        $category->delete();

        return back()->with('toast_info', 'Kategori berhasil dihapus.');
    }

    /**
     * Table & QR Code Management: /manager/tables (FR-01)
     */
    public function tablesIndex(): View
    {
        $tables = DiningTable::query()
            ->withCount('orders')
            ->orderBy('number')
            ->get();

        return view('manager.tables', [
            'tables' => $tables,
        ]);
    }

    public function tablesStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:10', 'unique:tables,number'],
            'label' => ['required', 'string', 'max:50'],
        ]);

        DiningTable::query()->create([
            'number' => trim($validated['number']),
            'label' => trim(strip_tags($validated['label'])),
            'qr_token' => DiningTable::generateToken(trim($validated['number'])),
            'is_active' => true,
        ]);

        return back()->with('toast_success', 'Meja dan kode QR baru berhasil dibuat.');
    }

    public function tablesRegenerate(DiningTable $table): RedirectResponse
    {
        $table->regenerateToken();

        return back()->with('toast_success', "Token QR untuk {$table->label} berhasil diperbarui. Kode QR lama tidak lagi berlaku.");
    }

    public function tablesQrSvg(DiningTable $table): Response
    {
        $url = route('table.scan', ['qr_token' => $table->qr_token]);
        $options = new QROptions([
            'outputBase64' => false,
            'addQuietzone' => true,
        ]);

        $svg = (new QRCode($options))->render($url);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'inline; filename="qr-meja-'.$table->number.'.svg"',
        ]);
    }

    /**
     * Reports & CSV Export: /manager/reports (FR-13, NFR-04)
     */
    public function reportsIndex(Request $request): View|StreamedResponse
    {
        $period = (string) $request->query('period', 'bulanan');
        $dateFrom = $request->query('from');
        $dateTo = $request->query('to');
        $groupBy = $request->query('group_by');

        $now = now()->timezone('Asia/Jakarta');

        // Resolve single month filter (YYYY-MM or month_num + year)
        $month = $request->query('month');
        if (! $month && $request->filled(['month_num', 'year'])) {
            $month = sprintf('%04d-%02d', (int) $request->query('year'), max(1, min(12, (int) $request->query('month_num'))));
        }
        if (! $month || ! preg_match('/^\d{4}-\d{2}$/', (string) $month)) {
            $month = $now->format('Y-m');
        }

        // Resolve month range filter (YYYY-MM or month_from_num + year_from / month_to_num + year_to)
        $monthFrom = $request->query('month_from');
        if (! $monthFrom && $request->filled(['month_from_num', 'year_from'])) {
            $monthFrom = sprintf('%04d-%02d', (int) $request->query('year_from'), max(1, min(12, (int) $request->query('month_from_num'))));
        }
        if (! $monthFrom || ! preg_match('/^\d{4}-\d{2}$/', (string) $monthFrom)) {
            $monthFrom = $now->copy()->subMonths(2)->format('Y-m');
        }

        $monthTo = $request->query('month_to');
        if (! $monthTo && $request->filled(['month_to_num', 'year_to'])) {
            $monthTo = sprintf('%04d-%02d', (int) $request->query('year_to'), max(1, min(12, (int) $request->query('month_to_num'))));
        }
        if (! $monthTo || ! preg_match('/^\d{4}-\d{2}$/', (string) $monthTo)) {
            $monthTo = $now->format('Y-m');
        }

        $resolved = $this->reportService->resolvePeriod(
            $period,
            $dateFrom,
            $dateTo,
            (string) $month,
            (string) $monthFrom,
            (string) $monthTo,
            is_string($groupBy) ? $groupBy : null
        );

        $summary = $this->reportService->getSummary($resolved['start'], $resolved['end']);
        $breakdown = $this->reportService->getPeriodBreakdown($resolved['start'], $resolved['end'], $resolved['group']);
        $topProducts = $this->reportService->getTopProducts($resolved['start'], $resolved['end'], 15);
        $categorySales = $this->reportService->getSalesByCategory($resolved['start'], $resolved['end']);

        if ($request->query('format') === 'csv') {
            $csvSlug = match ($period) {
                'per_bulan' => 'bulan-'.$month,
                'rentang_bulan' => 'rentang-'.$monthFrom.'-sd-'.$monthTo,
                default => $period,
            };

            return $this->streamCsvReport('laporan-manager-774hub-'.$csvSlug.'.csv', $resolved['label'], $summary, $breakdown);
        }

        [$selectedYear, $selectedMonthNum] = array_map('intval', explode('-', (string) $month));
        [$selectedYearFrom, $selectedMonthFromNum] = array_map('intval', explode('-', (string) $monthFrom));
        [$selectedYearTo, $selectedMonthToNum] = array_map('intval', explode('-', (string) $monthTo));

        $currentYear = (int) $now->format('Y');
        $availableYears = range($currentYear - 3, $currentYear + 1);

        return view('manager.reports', [
            'period' => $period,
            'dateFrom' => $resolved['start']->toDateString(),
            'dateTo' => $resolved['end']->toDateString(),
            'month' => $month,
            'monthFrom' => $monthFrom,
            'monthTo' => $monthTo,
            'selectedMonthNum' => $selectedMonthNum,
            'selectedYear' => $selectedYear,
            'selectedMonthFromNum' => $selectedMonthFromNum,
            'selectedYearFrom' => $selectedYearFrom,
            'selectedMonthToNum' => $selectedMonthToNum,
            'selectedYearTo' => $selectedYearTo,
            'availableMonths' => \App\Domain\Reporting\ReportService::INDONESIAN_MONTHS,
            'availableYears' => $availableYears,
            'activeGroupBy' => $resolved['group'],
            'periodLabel' => $resolved['label'],
            'summary' => $summary,
            'breakdown' => $breakdown,
            'topProducts' => $topProducts,
            'categorySales' => $categorySales,
        ]);
    }

    /**
     * Staff User Management (Non-Owner only for Manager): /manager/users
     */
    public function usersIndex(): View
    {
        $staffUsers = User::query()
            ->with('roles')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['kasir', 'kitchen', 'manager']))
            ->orderBy('name')
            ->get();

        return view('manager.users', [
            'staffUsers' => $staffUsers,
        ]);
    }

    public function usersStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'role' => ['required', 'in:kasir,kitchen,manager'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::query()->create([
            'name' => trim(strip_tags($validated['name'])),
            'email' => strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?: null,
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $user->syncRoles([$validated['role']]);

        return back()->with('toast_success', "Akun staf {$user->name} ({$validated['role']}) berhasil dibuat.");
    }

    public function usersUpdate(Request $request, User $user): RedirectResponse
    {
        if ($user->hasRole('owner')) {
            abort(403, 'Manager tidak memiliki wewenang untuk mengubah akun Owner.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'in:kasir,kitchen,manager'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $payload = [
            'name' => trim(strip_tags($validated['name'])),
            'email' => strtolower(trim($validated['email'])),
            'is_active' => $request->boolean('is_active', false),
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);
        $user->syncRoles([$validated['role']]);

        return back()->with('toast_success', "Akun staf {$user->name} berhasil diperbarui.");
    }

    protected function validatePromotionPayload(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('promotions', 'code')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:100'],
            'rule_type' => ['required', Rule::in(array_map(fn ($c) => $c->value, PromotionRuleType::cases()))],
            'description' => ['nullable', 'string', 'max:300'],
            'benefit_type' => ['required', 'in:percent,nominal,bonus_points'],
            'benefit_value' => ['required', 'integer', 'min:1'],
            'max_discount' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'target_segments' => ['nullable', 'array'],
            'min_transaction' => ['required', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_member_limit' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'conditions' => ['nullable', 'array'],
        ], [
            'ends_at.after_or_equal' => 'Tanggal berakhir promosi harus sama atau setelah tanggal mulai.',
        ]);

        if ($validated['benefit_type'] === 'percent' && $validated['benefit_value'] > 100) {
            throw ValidationException::withMessages([
                'benefit_value' => 'Nilai diskon persentase harus antara 1% hingga 100%.',
            ]);
        }

        return [
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim(strip_tags($validated['name'])),
            'rule_type' => $validated['rule_type'],
            'description' => $validated['description'] ?? null,
            'benefit_type' => $validated['benefit_type'],
            'benefit_value' => (int) $validated['benefit_value'],
            'max_discount' => ! empty($validated['max_discount']) ? (int) $validated['max_discount'] : null,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'target_segments' => ! empty($validated['target_segments']) ? array_values($validated['target_segments']) : ['all'],
            'min_transaction' => (int) $validated['min_transaction'],
            'usage_limit' => ! empty($validated['usage_limit']) ? (int) $validated['usage_limit'] : null,
            'per_member_limit' => (int) $validated['per_member_limit'],
            'conditions' => $validated['conditions'] ?? [],
            'is_active' => $request->boolean('is_active', false),
        ];
    }

    protected function streamCsvReport(string $filename, string $periodLabel, array $summary, array $breakdown): StreamedResponse
    {
        return response()->streamDownload(function () use ($periodLabel, $summary, $breakdown) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['774 HUB - Laporan Penjualan & Pendapatan Operasional']);
            fputcsv($out, ['Periode', $periodLabel]);
            fputcsv($out, ['Total Transaksi Dibayar/Selesai', $summary['tx_count']]);
            fputcsv($out, ['Penjualan Kotor (Subtotal)', $summary['gross_sales']]);
            fputcsv($out, ['Total Diskon Promo/Reward', $summary['total_discount']]);
            fputcsv($out, ['Total Pajak', $summary['total_tax']]);
            fputcsv($out, ['Total Pendapatan Bersih', $summary['total_revenue']]);
            fputcsv($out, ['Pendapatan Tunai', $summary['tunai_revenue']]);
            fputcsv($out, ['Pendapatan QRIS', $summary['qris_revenue']]);
            fputcsv($out, []);
            fputcsv($out, [
                'Periode',
                'Jumlah Transaksi',
                'Item Terjual',
                'Penjualan Kotor (Rp)',
                'Diskon (Rp)',
                'Pajak (Rp)',
                'Pendapatan Bersih (Rp)',
                'Tunai (Rp)',
                'QRIS (Rp)',
                'Transaksi Member',
                'Transaksi Guest',
            ]);

            foreach ($breakdown as $row) {
                fputcsv($out, [
                    $row['period_label'],
                    $row['tx_count'],
                    $row['items_sold'],
                    $row['gross_sales'],
                    $row['discount_amount'],
                    $row['tax_amount'],
                    $row['total_revenue'],
                    $row['tunai_revenue'],
                    $row['qris_revenue'],
                    $row['member_tx'],
                    $row['guest_tx'],
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
