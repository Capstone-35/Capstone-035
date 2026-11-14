<?php

namespace App\Http\Controllers\Owner;

use App\Domain\Reporting\ReportService;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\RfmAnalysis;
use App\Models\RfmSegment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnerController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * K1 Owner Executive Dashboard: /owner (FR-13)
     */
    public function dashboard(Request $request): View
    {
        $period = (string) $request->query('period', 'bulanan');
        $dateFrom = $request->query('from');
        $dateTo = $request->query('to');

        $resolved = $this->reportService->resolvePeriod($period, $dateFrom, $dateTo);
        $summary = $this->reportService->getSummary($resolved['start'], $resolved['end']);
        $breakdown = $this->reportService->getPeriodBreakdown($resolved['start'], $resolved['end'], $resolved['group']);
        $topProducts = $this->reportService->getTopProducts($resolved['start'], $resolved['end'], 10);
        $categorySales = $this->reportService->getSalesByCategory($resolved['start'], $resolved['end']);
        $rfmDistribution = $this->reportService->getRfmDistribution();
        $promoPerformance = $this->reportService->getPromotionPerformance();

        return view('owner.dashboard', [
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
        ]);
    }

    /**
     * K2 Sales Report: /owner/reports/sales (FR-13, NFR-04)
     */
    public function salesReport(Request $request): View|StreamedResponse
    {
        $period = (string) $request->query('period', 'bulanan');
        $dateFrom = $request->query('from');
        $dateTo = $request->query('to');

        $resolved = $this->reportService->resolvePeriod($period, $dateFrom, $dateTo);
        $summary = $this->reportService->getSummary($resolved['start'], $resolved['end']);
        $breakdown = $this->reportService->getPeriodBreakdown($resolved['start'], $resolved['end'], $resolved['group']);
        $topProducts = $this->reportService->getTopProducts($resolved['start'], $resolved['end'], 20);
        $categorySales = $this->reportService->getSalesByCategory($resolved['start'], $resolved['end']);

        if ($request->query('format') === 'csv') {
            return $this->streamCsv('laporan-penjualan-774hub-'.$period.'.csv', 'Laporan Penjualan 774 HUB', $resolved['label'], $summary, $breakdown);
        }

        return view('owner.sales-report', [
            'period' => $period,
            'dateFrom' => $resolved['start']->toDateString(),
            'dateTo' => $resolved['end']->toDateString(),
            'periodLabel' => $resolved['label'],
            'summary' => $summary,
            'breakdown' => $breakdown,
            'topProducts' => $topProducts,
            'categorySales' => $categorySales,
        ]);
    }

    /**
     * K3 Revenue Report: /owner/reports/revenue (FR-13, NFR-04)
     */
    public function revenueReport(Request $request): View|StreamedResponse
    {
        $period = (string) $request->query('period', 'bulanan');
        $dateFrom = $request->query('from');
        $dateTo = $request->query('to');

        $resolved = $this->reportService->resolvePeriod($period, $dateFrom, $dateTo);
        $summary = $this->reportService->getSummary($resolved['start'], $resolved['end']);
        $breakdown = $this->reportService->getPeriodBreakdown($resolved['start'], $resolved['end'], $resolved['group']);

        if ($request->query('format') === 'csv') {
            return $this->streamCsv('laporan-pendapatan-774hub-'.$period.'.csv', 'Laporan Pendapatan Operasional 774 HUB', $resolved['label'], $summary, $breakdown);
        }

        return view('owner.revenue-report', [
            'period' => $period,
            'dateFrom' => $resolved['start']->toDateString(),
            'dateTo' => $resolved['end']->toDateString(),
            'periodLabel' => $resolved['label'],
            'summary' => $summary,
            'breakdown' => $breakdown,
        ]);
    }

    /**
     * K5 Customer & RFM Summary (Read-only for Owner): /owner/customers
     */
    public function customers(): View
    {
        $latestAnalysis = RfmAnalysis::query()->latest('id')->first();
        $rfmDistribution = $this->reportService->getRfmDistribution();
        $segments = RfmSegment::query()->orderBy('priority')->get();

        $topMembers = Member::query()
            ->with(['user', 'latestRfmResult.segment'])
            ->withCount(['orders as successful_orders_count' => fn ($q) => $q->successful()])
            ->withSum(['orders as total_spent' => fn ($q) => $q->successful()], 'total_amount')
            ->orderByDesc('total_spent')
            ->limit(20)
            ->get();

        return view('owner.customers', [
            'latestAnalysis' => $latestAnalysis,
            'rfmDistribution' => $rfmDistribution,
            'segments' => $segments,
            'topMembers' => $topMembers,
            'totalMembers' => Member::query()->count(),
        ]);
    }

    /**
     * K6 Promotion Effectiveness Summary (Read-only for Owner): /owner/promotions
     */
    public function promotions(): View
    {
        $performance = $this->reportService->getPromotionPerformance();

        return view('owner.promotions', [
            'performance' => $performance,
        ]);
    }

    /**
     * Staff & Role Management (All staff including Owner): /owner/users
     */
    public function usersIndex(): View
    {
        $staffUsers = User::query()
            ->with('roles')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['kasir', 'kitchen', 'manager', 'owner']))
            ->orderBy('name')
            ->get();

        return view('owner.users', [
            'staffUsers' => $staffUsers,
        ]);
    }

    public function usersStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'role' => ['required', 'in:kasir,kitchen,manager,owner'],
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'in:kasir,kitchen,manager,owner'],
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

        return back()->with('toast_success', "Akun {$user->name} berhasil diperbarui.");
    }

    protected function streamCsv(string $filename, string $title, string $periodLabel, array $summary, array $breakdown): StreamedResponse
    {
        return response()->streamDownload(function () use ($title, $periodLabel, $summary, $breakdown) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [$title]);
            fputcsv($out, ['Periode', $periodLabel]);
            fputcsv($out, ['Total Transaksi Dibayar/Selesai', $summary['tx_count']]);
            fputcsv($out, ['Penjualan Kotor (Subtotal Rp)', $summary['gross_sales']]);
            fputcsv($out, ['Total Diskon (Rp)', $summary['total_discount']]);
            fputcsv($out, ['Total Pajak (Rp)', $summary['total_tax']]);
            fputcsv($out, ['Total Pendapatan Bersih (Rp)', $summary['total_revenue']]);
            fputcsv($out, ['Pendapatan Tunai (Rp)', $summary['tunai_revenue']]);
            fputcsv($out, ['Pendapatan QRIS (Rp)', $summary['qris_revenue']]);
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
