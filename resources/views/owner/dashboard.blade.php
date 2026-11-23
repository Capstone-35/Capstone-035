@extends('layouts.staff', ['title' => 'Owner Dashboard'])

@section('heading', 'Owner — Eksekutif Dashboard (FR-13, NFR-04)')

@section('content')
<div class="space-y-6">
    <!-- Period Filter -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex flex-wrap items-center gap-2">
            <span class="font-bold uppercase text-[#6F6259] whitespace-nowrap">Periode:</span>
            <a href="{{ route('owner.dashboard', ['period' => 'harian']) }}"
               class="min-h-[38px] inline-flex items-center px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap {{ $period === 'harian' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Hari Ini
            </a>
            <a href="{{ route('owner.dashboard', ['period' => 'mingguan']) }}"
               class="min-h-[38px] inline-flex items-center px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap {{ $period === 'mingguan' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Mingguan (7 Hari)
            </a>
            <a href="{{ route('owner.dashboard', ['period' => 'bulanan']) }}"
               class="min-h-[38px] inline-flex items-center px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap {{ $period === 'bulanan' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Bulanan (30 Hari)
            </a>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <a href="{{ route('owner.reports.sales', ['period' => $period]) }}" class="min-h-[40px] flex-1 sm:flex-none inline-flex items-center justify-center px-3.5 py-2 rounded-xl bg-white border border-[#D6CBBC] font-bold text-[#3B2A20] hover:bg-[#F2ECE1] whitespace-nowrap">
                Laporan Penjualan →
            </a>
            <a href="{{ route('owner.reports.revenue', ['period' => $period]) }}" class="min-h-[40px] flex-1 sm:flex-none inline-flex items-center justify-center px-3.5 py-2 rounded-xl bg-[#3B2A20] font-bold text-white hover:bg-[#2A1E17] whitespace-nowrap">
                Laporan Pendapatan →
            </a>
        </div>
    </div>

    <!-- Core Executive KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <x-kpi-card
            label="Total Pendapatan Bersih (Revenue)"
            :value="'Rp' . number_format($summary['total_revenue'], 0, ',', '.')"
            subtext="Tunai & QRIS (Dibayar/Selesai)"
            :accent="true"
        />
        <x-kpi-card
            label="Penjualan Kotor (Gross Sales)"
            :value="'Rp' . number_format($summary['gross_sales'], 0, ',', '.')"
            :subtext="'Potongan Diskon: Rp' . number_format($summary['total_discount'], 0, ',', '.')"
        />
        <x-kpi-card
            label="Total Transaksi Dilayani"
            :value="$summary['tx_count']"
            :subtext="'AOV: Rp' . number_format($summary['aov'], 0, ',', '.') . ' / transaksi'"
        />
        <x-kpi-card
            label="Repeat Orders Member"
            :value="$summary['repeat_orders_count'] . ' Order'"
            :subtext="'Member Baru: ' . $summary['new_members_count'] . ' orang'"
        />
    </div>

    <!-- Revenue Streams -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-[#E6DED2] shadow-sm min-w-0">
            <span class="text-xs uppercase font-bold text-[#6F6259]">Pendapatan Tunai di Kasir</span>
            <div class="text-xl font-black text-[#1F1A17] mt-1 tabular-nums break-words">
                Rp{{ number_format($summary['tunai_revenue'], 0, ',', '.') }}
            </div>
            <div class="text-xs text-[#6F6259] mt-1">{{ $summary['tunai_count'] }} Transaksi Tunai Terverifikasi</div>
        </div>

        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-[#E6DED2] shadow-sm min-w-0">
            <span class="text-xs uppercase font-bold text-[#6F6259]">Pendapatan QRIS Digital</span>
            <div class="text-xl font-black text-[#1F1A17] mt-1 tabular-nums break-words">
                Rp{{ number_format($summary['qris_revenue'], 0, ',', '.') }}
            </div>
            <div class="text-xs text-[#6F6259] mt-1">{{ $summary['qris_count'] }} Transaksi QRIS Terverifikasi Gateway</div>
        </div>

        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-[#E6DED2] shadow-sm min-w-0">
            <span class="text-xs uppercase font-bold text-[#6F6259]">Rata-rata Waktu Sajian Dapur</span>
            <div class="text-xl font-black text-[#1F1A17] mt-1 tabular-nums">
                {{ $summary['avg_prep_minutes'] }} Menit
            </div>
            <div class="text-xs text-[#6F6259] mt-1">Kecepatan Pelayanan (Dibayar &rarr; Siap)</div>
        </div>
    </div>

    <!-- Breakdown Table -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E6DED2]">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">Tren Penjualan & Pendapatan Berkala ({{ $periodLabel }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3 whitespace-nowrap">Periode</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Transaksi</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Porsi Terjual</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Penjualan Kotor</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Diskon Promo</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Pendapatan Bersih</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Member vs Tamu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($breakdown as $b)
                        <tr class="hover:bg-[#FAF6EF]/60">
                            <td class="px-4 py-3.5 font-bold text-[#1F1A17] whitespace-nowrap">{{ $b['period_label'] }}</td>
                            <td class="px-4 py-3.5 text-center font-bold tabular-nums whitespace-nowrap">{{ $b['tx_count'] }}</td>
                            <td class="px-4 py-3.5 text-center tabular-nums whitespace-nowrap">{{ $b['items_sold'] }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums whitespace-nowrap">Rp{{ number_format($b['gross_sales'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-[#1F7A4C] whitespace-nowrap">-Rp{{ number_format($b['discount_amount'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right font-extrabold text-[#3B2A20] tabular-nums whitespace-nowrap">Rp{{ number_format($b['total_revenue'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-center text-[#6F6259] whitespace-nowrap">{{ $b['member_tx'] }} / {{ $b['guest_tx'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-[#6F6259]">Belum ada transaksi pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
