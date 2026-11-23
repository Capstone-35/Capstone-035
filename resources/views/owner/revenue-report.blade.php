@extends('layouts.staff', ['title' => 'Laporan Pendapatan Owner'])

@section('heading', 'Owner — Laporan Pendapatan Operasional (Revenue Report)')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('owner.reports.revenue', ['period' => 'harian']) }}"
               class="min-h-[38px] inline-flex items-center px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap {{ $period === 'harian' ? 'bg-[#3B2A20] text-white' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Harian
            </a>
            <a href="{{ route('owner.reports.revenue', ['period' => 'mingguan']) }}"
               class="min-h-[38px] inline-flex items-center px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap {{ $period === 'mingguan' ? 'bg-[#3B2A20] text-white' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Mingguan
            </a>
            <a href="{{ route('owner.reports.revenue', ['period' => 'bulanan']) }}"
               class="min-h-[38px] inline-flex items-center px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap {{ $period === 'bulanan' ? 'bg-[#3B2A20] text-white' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Bulanan
            </a>
        </div>

        <a href="{{ route('owner.reports.revenue', ['period' => $period, 'from' => $dateFrom, 'to' => $dateTo, 'format' => 'csv']) }}"
           class="w-full sm:w-auto min-h-[40px] inline-flex items-center justify-center px-4 py-2 rounded-xl bg-[#1F7A4C] text-white font-extrabold hover:bg-emerald-800 transition whitespace-nowrap">
            Unduh CSV Pendapatan
        </a>
    </div>

    <!-- Revenue Breakdown Table -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E6DED2]">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">Arus Pendapatan Berdasarkan Metode Pembayaran ({{ $periodLabel }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[660px] text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3 whitespace-nowrap">Tanggal / Periode</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Transaksi</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Pendapatan Tunai</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Pendapatan QRIS</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Pajak</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Total Pendapatan Masuk</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($breakdown as $b)
                        <tr class="hover:bg-[#FAF6EF]/60">
                            <td class="px-4 py-3.5 font-bold text-[#1F1A17] whitespace-nowrap">{{ $b['period_label'] }}</td>
                            <td class="px-4 py-3.5 text-center font-bold tabular-nums whitespace-nowrap">{{ $b['tx_count'] }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums whitespace-nowrap">Rp{{ number_format($b['tunai_revenue'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums whitespace-nowrap">Rp{{ number_format($b['qris_revenue'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums whitespace-nowrap">Rp{{ number_format($b['tax_amount'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right font-extrabold text-[#3B2A20] tabular-nums whitespace-nowrap">
                                Rp{{ number_format($b['total_revenue'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-[#6F6259]">Belum ada data pendapatan pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
