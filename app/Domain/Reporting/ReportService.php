<?php

namespace App\Domain\Reporting;

use App\Enums\InvoiceDeliveryStatus;
use App\Enums\OrderStatus;
use App\Models\InvoiceDelivery;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointLedger;
use App\Models\Promotion;
use App\Models\PromotionReturn;
use App\Models\PromotionUsage;
use App\Models\RfmAnalysis;
use App\Models\RfmResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Resolves period filter (harian, mingguan, bulanan, custom) into [Carbon $start, Carbon $end, string $groupBy, string $label].
     *
     * @return array{start: Carbon, end: Carbon, group: string, label: string}
     */
    public function resolvePeriod(string $period = 'bulanan', ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $now = now()->timezone('Asia/Jakarta');

        if ($period === 'harian' || $period === 'today') {
            return [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'group' => 'harian',
                'label' => 'Hari Ini ('.$now->format('d M Y').')',
            ];
        }

        if ($period === 'mingguan' || $period === 'week') {
            $start = $now->copy()->subDays(6)->startOfDay();

            return [
                'start' => $start,
                'end' => $now->copy()->endOfDay(),
                'group' => 'harian',
                'label' => '7 Hari Terakhir ('.$start->format('d M').' – '.$now->format('d M Y').')',
            ];
        }

        if ($period === 'custom' && $dateFrom && $dateTo) {
            $start = Carbon::parse($dateFrom, 'Asia/Jakarta')->startOfDay();
            $end = Carbon::parse($dateTo, 'Asia/Jakarta')->endOfDay();
            if ($end->lt($start)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            return [
                'start' => $start,
                'end' => $end,
                'group' => $start->diffInDays($end) > 60 ? 'bulanan' : 'harian',
                'label' => $start->format('d M Y').' – '.$end->format('d M Y'),
            ];
        }

        // Default 'bulanan' (last 30 days)
        $start = $now->copy()->subDays(29)->startOfDay();

        return [
            'start' => $start,
            'end' => $now->copy()->endOfDay(),
            'group' => 'harian',
            'label' => '30 Hari Terakhir ('.$start->format('d M').' – '.$now->format('d M Y').')',
        ];
    }

    /**
     * Core Sales & Revenue summary for a given period (FR-13, Section 0.3, Section 17).
     * Revenue = sum of orders.total_amount where status in (Dibayar, Diproses, Siap, Selesai).
     * Gross sales = sum of orders.subtotal for paid orders.
     */
    public function getSummary(Carbon $start, Carbon $end): array
    {
        $paidOrders = Order::query()
            ->paidRevenue()
            ->whereBetween('created_at', [$start, $end]);

        $txCount = (clone $paidOrders)->count();
        $grossSales = (int) (clone $paidOrders)->sum('subtotal');
        $totalDiscount = (int) (clone $paidOrders)->sum('discount_amount');
        $totalTax = (int) (clone $paidOrders)->sum('tax_amount');
        $totalRevenue = (int) (clone $paidOrders)->sum('total_amount');
        $aov = $txCount > 0 ? (int) round($totalRevenue / $txCount) : 0;

        $tunaiRevenue = (int) (clone $paidOrders)->where('payment_method', 'tunai')->sum('total_amount');
        $tunaiCount = (clone $paidOrders)->where('payment_method', 'tunai')->count();

        $qrisRevenue = (int) (clone $paidOrders)->where('payment_method', 'qris')->sum('total_amount');
        $qrisCount = (clone $paidOrders)->where('payment_method', 'qris')->count();

        $memberOrdersCount = (clone $paidOrders)->whereNotNull('member_id')->count();
        $guestOrdersCount = (clone $paidOrders)->whereNull('member_id')->count();
        $repeatOrdersCount = (clone $paidOrders)->where('is_repeat_order', true)->count();

        $cancelledCount = Order::query()
            ->where('status', OrderStatus::Dibatalkan->value)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $promoUsageCount = PromotionUsage::query()
            ->whereBetween('used_at', [$start, $end])
            ->count();

        $newMembersCount = Member::query()
            ->whereBetween('joined_at', [$start, $end])
            ->count();

        $activeMembersCount = Order::query()
            ->paidRevenue()
            ->whereNotNull('member_id')
            ->whereBetween('created_at', [$start, $end])
            ->distinct('member_id')
            ->count('member_id');

        $pointsEarned = (int) PointLedger::query()
            ->where('type', 'earn')
            ->whereBetween('created_at', [$start, $end])
            ->sum('points');

        $pointsRedeemed = abs((int) PointLedger::query()
            ->where('type', 'redeem')
            ->whereBetween('created_at', [$start, $end])
            ->sum('points'));

        // Average prep time Dibayar -> Siap in minutes
        $prepOrders = Order::query()
            ->whereNotNull('paid_at')
            ->whereNotNull('ready_at')
            ->whereBetween('created_at', [$start, $end])
            ->get(['paid_at', 'ready_at']);

        $avgPrepMinutes = 0.0;
        if ($prepOrders->count() > 0) {
            $totalSecs = $prepOrders->sum(fn ($o) => max(0, $o->paid_at->diffInSeconds($o->ready_at)));
            $avgPrepMinutes = round(($totalSecs / $prepOrders->count()) / 60, 1);
        }

        return [
            'tx_count' => $txCount,
            'gross_sales' => $grossSales,
            'total_discount' => $totalDiscount,
            'total_tax' => $totalTax,
            'total_revenue' => $totalRevenue,
            'aov' => $aov,
            'tunai_revenue' => $tunaiRevenue,
            'tunai_count' => $tunaiCount,
            'qris_revenue' => $qrisRevenue,
            'qris_count' => $qrisCount,
            'member_orders_count' => $memberOrdersCount,
            'guest_orders_count' => $guestOrdersCount,
            'repeat_orders_count' => $repeatOrdersCount,
            'cancelled_count' => $cancelledCount,
            'promo_usage_count' => $promoUsageCount,
            'new_members_count' => $newMembersCount,
            'active_members_count' => $activeMembersCount,
            'points_earned' => $pointsEarned,
            'points_redeemed' => $pointsRedeemed,
            'avg_prep_minutes' => $avgPrepMinutes,
        ];
    }

    /**
     * Time-series rows grouped by day, week, or month for Sales & Revenue charts and tables.
     *
     * @return array<int, array{
     *   period_key: string,
     *   period_label: string,
     *   tx_count: int,
     *   items_sold: int,
     *   gross_sales: int,
     *   discount_amount: int,
     *   tax_amount: int,
     *   total_revenue: int,
     *   tunai_revenue: int,
     *   qris_revenue: int,
     *   member_tx: int,
     *   guest_tx: int
     * }>
     */
    public function getPeriodBreakdown(Carbon $start, Carbon $end, string $groupBy = 'harian'): array
    {
        $orders = Order::query()
            ->paidRevenue()
            ->withSum('items as items_qty', 'quantity')
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();

        $buckets = [];

        foreach ($orders as $order) {
            $dt = $order->created_at->copy()->timezone('Asia/Jakarta');
            if ($groupBy === 'bulanan') {
                $key = $dt->format('Y-m');
                $label = $dt->translatedFormat('M Y');
            } elseif ($groupBy === 'mingguan') {
                $weekStart = $dt->copy()->startOfWeek();
                $key = $weekStart->format('Y-W');
                $label = 'Minggu '.$weekStart->format('d M Y');
            } else {
                $key = $dt->format('Y-m-d');
                $label = $dt->format('d M Y');
            }

            if (! isset($buckets[$key])) {
                $buckets[$key] = [
                    'period_key' => $key,
                    'period_label' => $label,
                    'tx_count' => 0,
                    'items_sold' => 0,
                    'gross_sales' => 0,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'total_revenue' => 0,
                    'tunai_revenue' => 0,
                    'qris_revenue' => 0,
                    'member_tx' => 0,
                    'guest_tx' => 0,
                ];
            }

            $buckets[$key]['tx_count']++;
            $buckets[$key]['items_sold'] += (int) ($order->items_qty ?? 0);
            $buckets[$key]['gross_sales'] += (int) $order->subtotal;
            $buckets[$key]['discount_amount'] += (int) $order->discount_amount;
            $buckets[$key]['tax_amount'] += (int) $order->tax_amount;
            $buckets[$key]['total_revenue'] += (int) $order->total_amount;

            if ($order->payment_method?->value === 'qris') {
                $buckets[$key]['qris_revenue'] += (int) $order->total_amount;
            } else {
                $buckets[$key]['tunai_revenue'] += (int) $order->total_amount;
            }

            if ($order->member_id) {
                $buckets[$key]['member_tx']++;
            } else {
                $buckets[$key]['guest_tx']++;
            }
        }

        return array_values($buckets);
    }

    /**
     * Top selling products in the period.
     */
    public function getTopProducts(Carbon $start, Carbon $end, int $limit = 10): array
    {
        return OrderItem::query()
            ->select([
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.line_total) as total_sales'),
            ])
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', [
                OrderStatus::Dibayar->value,
                OrderStatus::Diproses->value,
                OrderStatus::Siap->value,
                OrderStatus::Selesai->value,
            ])
            ->whereBetween('orders.created_at', [$start, $end])
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'product_name' => $row->product_name,
                'total_qty' => (int) $row->total_qty,
                'total_sales' => (int) $row->total_sales,
            ])
            ->all();
    }

    /**
     * Sales by category in the period.
     */
    public function getSalesByCategory(Carbon $start, Carbon $end): array
    {
        return OrderItem::query()
            ->select([
                DB::raw("COALESCE(categories.name, 'Lainnya') as category_name"),
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.line_total) as total_sales'),
            ])
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereIn('orders.status', [
                OrderStatus::Dibayar->value,
                OrderStatus::Diproses->value,
                OrderStatus::Siap->value,
                OrderStatus::Selesai->value,
            ])
            ->whereBetween('orders.created_at', [$start, $end])
            ->groupBy('category_name')
            ->orderByDesc('total_sales')
            ->get()
            ->map(fn ($row) => [
                'category_name' => $row->category_name,
                'total_qty' => (int) $row->total_qty,
                'total_sales' => (int) $row->total_sales,
            ])
            ->all();
    }

    /**
     * RFM segment distribution from the latest analysis run.
     */
    public function getRfmDistribution(): array
    {
        $latest = RfmAnalysis::query()->latest('id')->first();
        if (! $latest) {
            return [];
        }

        return RfmResult::query()
            ->select(['segment_name', DB::raw('COUNT(*) as member_count'), DB::raw('SUM(monetary) as total_monetary')])
            ->where('rfm_analysis_id', $latest->id)
            ->groupBy('segment_name')
            ->orderByDesc('member_count')
            ->get()
            ->map(fn ($r) => [
                'segment_name' => $r->segment_name,
                'member_count' => (int) $r->member_count,
                'total_monetary' => (int) $r->total_monetary,
            ])
            ->all();
    }

    /**
     * Promotion performance evaluation including Promo Return Tracking (Rule 6).
     */
    public function getPromotionPerformance(): array
    {
        $promotions = Promotion::withTrashed()->orderBy('id')->get();
        $rows = [];

        foreach ($promotions as $promo) {
            $usages = PromotionUsage::query()
                ->with(['order', 'returnRecord'])
                ->where('promotion_id', $promo->id)
                ->get();

            $timesUsed = $usages->count();
            $totalDiscount = (int) $usages->sum('discount_amount');
            $ordersRevenue = (int) $usages->sum(fn ($u) => (int) ($u->order?->total_amount ?? 0));
            $uniqueMembers = $usages->pluck('member_id')->unique()->count();

            $returnedUsages = $usages->filter(fn ($u) => $u->returnRecord && $u->returnRecord->returned_order_id !== null);
            $returnedCount = $returnedUsages->count();
            $returnRate = $timesUsed > 0 ? round(($returnedCount / $timesUsed) * 100, 1) : 0.0;
            $avgDaysToReturn = $returnedCount > 0
                ? round($returnedUsages->avg(fn ($u) => (int) $u->returnRecord->days_to_return), 1)
                : null;

            $rows[] = [
                'promotion' => $promo,
                'times_used' => $timesUsed,
                'total_discount' => $totalDiscount,
                'orders_revenue' => $ordersRevenue,
                'unique_members' => $uniqueMembers,
                'returned_count' => $returnedCount,
                'return_rate' => $returnRate,
                'avg_days_to_return' => $avgDaysToReturn,
            ];
        }

        return $rows;
    }

    /**
     * Operational stats for Kasir POS Dashboard (Section 17).
     */
    public function getPosOperationalStats(): array
    {
        $todayStart = now()->timezone('Asia/Jakarta')->startOfDay();
        $todayEnd = now()->timezone('Asia/Jakarta')->endOfDay();

        $todayTxCount = Order::query()->whereBetween('created_at', [$todayStart, $todayEnd])->count();
        $unpaidOrders = Order::query()
            ->with(['table', 'items'])
            ->where('status', OrderStatus::MenungguPembayaran->value)
            ->orderByDesc('created_at')
            ->get();

        $activeQueueCount = Order::query()
            ->whereIn('status', [
                OrderStatus::Dibayar->value,
                OrderStatus::Diproses->value,
                OrderStatus::Siap->value,
            ])
            ->count();

        $readyToHandover = Order::query()
            ->with(['table', 'items'])
            ->where('status', OrderStatus::Siap->value)
            ->orderBy('ready_at')
            ->get();

        $failedDeliveries = InvoiceDelivery::query()
            ->with('invoice.order')
            ->where('status', InvoiceDeliveryStatus::Failed->value)
            ->orderByDesc('updated_at')
            ->limit(15)
            ->get();

        $recentOrders = Order::query()
            ->with(['table', 'member.user', 'invoice.latestDelivery'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return [
            'today_tx_count' => $todayTxCount,
            'unpaid_count' => $unpaidOrders->count(),
            'unpaid_orders' => $unpaidOrders,
            'active_queue_count' => $activeQueueCount,
            'ready_to_handover' => $readyToHandover,
            'failed_deliveries_count' => $failedDeliveries->count(),
            'failed_deliveries' => $failedDeliveries,
            'recent_orders' => $recentOrders,
        ];
    }
}
