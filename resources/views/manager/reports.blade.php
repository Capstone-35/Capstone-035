@extends('layouts.staff', ['title' => 'Laporan Operasional'])

@section('heading', 'Manager — Laporan Penjualan & Pendapatan (FR-13, NFR-04)')

@section('content')
<div class="space-y-6">
    <!-- Filter and CSV Export Bar -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2">
            <a href="{{ route('manager.reports.index', ['period' => 'harian']) }}"
               class="px-3 py-1.5 rounded-xl font-bold {{ $period === 'harian' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Harian (Hari Ini)
            </a>
            <a href="{{ route('manager.reports.index', ['period' => 'mingguan']) }}"
               class="px-3 py-1.5 rounded-xl font-bold {{ $period === 'mingguan' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Mingguan (7 Hari)
            </a>
            <a href="{{ route('manager.reports.index', ['period' => 'bulanan']) }}"
               class="px-3 py-1.5 rounded-xl font-bold {{ $period === 'bulanan' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Bulanan (30 Hari)
            </a>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('manager.reports.index', ['period' => $period, 'from' => $dateFrom, 'to' => $dateTo, 'format' => 'csv']) }}"
               class="px-4 py-2 rounded-xl bg-[#1F7A4C] text-white font-extrabold hover:bg-emerald-800 transition">
                Ekspor Laporan (CSV)
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-kpi-card
            label="Total Pendapatan Bersih"
            :value="'Rp' . number_format($summary['total_revenue'], 0, ',', '.')"
            subtext="Tunai & QRIS Lunas"
            :accent="true"
        />
        <x-kpi-card
            label="Penjualan Kotor (Subtotal)"
            :value="'Rp' . number_format($summary['gross_sales'], 0, ',', '.')"
            :subtext="'Potongan Diskon: Rp' . number_format($summary['total_discount'], 0, ',', '.')"
        />
        <x-kpi-card
            label="Transaksi Berhasil"
            :value="$summary['tx_count']"
            :subtext="'AOV: Rp' . number_format($summary['aov'], 0, ',', '.')"
        />
        <x-kpi-card
            label="Total Pajak Diterima"
            :value="'Rp' . number_format($summary['total_tax'], 0, ',', '.')"
            subtext="Snapshot Pajak Transaksi"
        />
    </div>

    <!-- Period Breakdown Table -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E6DED2]">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">Rincian Per Tanggal / Periode</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3 text-center">Jumlah Transaksi</th>
                        <th class="px-4 py-3 text-center">Item Terjual</th>
                        <th class="px-4 py-3 text-right">Penjualan Kotor</th>
                        <th class="px-4 py-3 text-right">Diskon</th>
                        <th class="px-4 py-3 text-right">Pajak</th>
                        <th class="px-4 py-3 text-right">Total Pendapatan</th>
                        <th class="px-4 py-3 text-right">Tunai / QRIS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($breakdown as $row)
                        <tr class="hover:bg-[#FAF6EF]/60">
                            <td class="px-4 py-3.5 font-bold text-[#1F1A17]">{{ $row['period_label'] }}</td>
                            <td class="px-4 py-3.5 text-center font-bold tabular-nums">{{ $row['tx_count'] }}</td>
                            <td class="px-4 py-3.5 text-center tabular-nums">{{ $row['items_sold'] }}</td>
                            <td class="px-4 py-3.5 text-right font-mono tabular-nums">Rp{{ number_format($row['gross_sales'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right font-mono text-[#1F7A4C] tabular-nums">Rp{{ number_format($row['discount_amount'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right font-mono tabular-nums">Rp{{ number_format($row['tax_amount'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right font-extrabold text-[#3B2A20] tabular-nums">Rp{{ number_format($row['total_revenue'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right text-[#6F6259] tabular-nums">
                                Rp{{ number_format($row['tunai_revenue'], 0, ',', '.') }} / Rp{{ number_format($row['qris_revenue'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-[#6F6259]">
                                Tidak ada data transaksi pada rentang periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
