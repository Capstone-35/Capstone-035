@extends('layouts.staff', ['title' => 'Owner Dashboard'])

@section('heading', 'Owner — Eksekutif Dashboard (FR-13, NFR-04)')

@section('content')
<div class="space-y-6">
    <!-- Period Filter -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2">
            <span class="font-bold uppercase text-[#6F6259]">Periode Eksekutif:</span>
            <a href="{{ route('owner.dashboard', ['period' => 'harian']) }}"
               class="px-3.5 py-1.5 rounded-xl font-bold {{ $period === 'harian' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Hari Ini
            </a>
            <a href="{{ route('owner.dashboard', ['period' => 'mingguan']) }}"
               class="px-3.5 py-1.5 rounded-xl font-bold {{ $period === 'mingguan' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Mingguan (7 Hari)
            </a>
            <a href="{{ route('owner.dashboard', ['period' => 'bulanan']) }}"
               class="px-3.5 py-1.5 rounded-xl font-bold {{ $period === 'bulanan' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Bulanan (30 Hari)
            </a>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('owner.reports.sales') }}" class="px-3.5 py-2 rounded-xl bg-white border border-[#D6CBBC] font-bold text-[#3B2A20] hover:bg-[#F2ECE1]">
                Laporan Penjualan Lengkap →
            </a>
            <a href="{{ route('owner.reports.revenue') }}" class="px-3.5 py-2 rounded-xl bg-[#3B2A20] font-bold text-white hover:bg-[#2A1E17]">
                Laporan Pendapatan →
            </a>
        </div>
    </div>

    <!-- Core Executive KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 rounded-2xl bg-white border border-[#E6DED2] shadow-sm">
            <span class="text-xs uppercase font-bold text-[#6F6259]">Pendapatan Tunai di Kasir</span>
            <div class="text-xl font-black text-[#1F1A17] mt-1 tabular-nums">
                Rp{{ number_format($summary['tunai_revenue'], 0, ',', '.') }}
            </div>
            <div class="text-xs text-[#6F6259] mt-1">{{ $summary['tunai_count'] }} Transaksi Tunai Terverifikasi</div>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-[#E6DED2] shadow-sm">
            <span class="text-xs uppercase font-bold text-[#6F6259]">Pendapatan QRIS Digital</span>
            <div class="text-xl font-black text-[#1F1A17] mt-1 tabular-nums">
                Rp{{ number_format($summary['qris_revenue'], 0, ',', '.') }}
            </div>
            <div class="text-xs text-[#6F6259] mt-1">{{ $summary['qris_count'] }} Transaksi QRIS Terverifikasi Gateway</div>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-[#E6DED2] shadow-sm">
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
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3 text-center">Transaksi</th>
                        <th class="px-4 py-3 text-center">Porsi Terjual</th>
                        <th class="px-4 py-3 text-right">Penjualan Kotor</th>
                        <th class="px-4 py-3 text-right">Diskon Promo</th>
                        <th class="px-4 py-3 text-right">Pendapatan Bersih</th>
                        <th class="px-4 py-3 text-center">Member vs Tamu</th>
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
                            <td class="px-4 py-3.5 text-center text-[#6F6259]">{{ $b['member_tx'] }} / {{ $b['guest_tx'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
