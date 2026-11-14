@extends('layouts.staff', ['title' => 'Laporan Penjualan Owner'])

@section('heading', 'Owner — Laporan Penjualan (Sales Report)')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2">
            <a href="{{ route('owner.reports.sales', ['period' => 'harian']) }}"
               class="px-3 py-1.5 rounded-xl font-bold {{ $period === 'harian' ? 'bg-[#3B2A20] text-white' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Harian
            </a>
            <a href="{{ route('owner.reports.sales', ['period' => 'mingguan']) }}"
               class="px-3 py-1.5 rounded-xl font-bold {{ $period === 'mingguan' ? 'bg-[#3B2A20] text-white' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Mingguan
            </a>
            <a href="{{ route('owner.reports.sales', ['period' => 'bulanan']) }}"
               class="px-3 py-1.5 rounded-xl font-bold {{ $period === 'bulanan' ? 'bg-[#3B2A20] text-white' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Bulanan
            </a>
        </div>

        <a href="{{ route('owner.reports.sales', ['period' => $period, 'from' => $dateFrom, 'to' => $dateTo, 'format' => 'csv']) }}"
           class="px-4 py-2 rounded-xl bg-[#1F7A4C] text-white font-extrabold hover:bg-emerald-800 transition">
            Unduh CSV Penjualan
        </a>
    </div>

    <!-- Period Breakdown Table -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E6DED2]">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">Rincian Transaksi & Porsi Terjual ({{ $periodLabel }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3">Tanggal / Periode</th>
                        <th class="px-4 py-3 text-center">Transaksi</th>
                        <th class="px-4 py-3 text-center">Porsi Terjual</th>
                        <th class="px-4 py-3 text-right">Penjualan Kotor (Subtotal)</th>
                        <th class="px-4 py-3 text-right">Diskon Promo</th>
                        <th class="px-4 py-3 text-right">Pendapatan Bersih</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @foreach($breakdown as $b)
                        <tr class="hover:bg-[#FAF6EF]/60">
                            <td class="px-4 py-3.5 font-bold text-[#1F1A17]">{{ $b['period_label'] }}</td>
                            <td class="px-4 py-3.5 text-center font-bold tabular-nums">{{ $b['tx_count'] }}</td>
                            <td class="px-4 py-3.5 text-center tabular-nums">{{ $b['items_sold'] }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums">Rp{{ number_format($b['gross_sales'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-[#1F7A4C]">-Rp{{ number_format($b['discount_amount'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right font-extrabold text-[#3B2A20] tabular-nums">Rp{{ number_format($b['total_revenue'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
