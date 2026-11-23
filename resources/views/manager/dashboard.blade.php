@extends('layouts.staff', ['title' => 'Manager Dashboard'])

@section('heading', 'Manager — Dashboard Kinerja & Analisis RFM')

@section('content')
<div class="space-y-6">
    <!-- Period Selector -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold uppercase text-[#6F6259] whitespace-nowrap">Periode:</span>
            <a href="{{ route('manager.dashboard', ['period' => 'harian']) }}"
               class="min-h-[38px] inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap {{ $period === 'harian' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Hari Ini
            </a>
            <a href="{{ route('manager.dashboard', ['period' => 'mingguan']) }}"
               class="min-h-[38px] inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap {{ $period === 'mingguan' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                7 Hari Terakhir
            </a>
            <a href="{{ route('manager.dashboard', ['period' => 'bulanan']) }}"
               class="min-h-[38px] inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap {{ $period === 'bulanan' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                30 Hari Terakhir
            </a>
        </div>

        <form method="GET" action="{{ route('manager.dashboard') }}" class="flex flex-wrap items-center gap-2 text-xs w-full sm:w-auto">
            <input type="hidden" name="period" value="custom">
            <input type="date" name="from" required value="{{ $dateFrom }}" class="flex-1 sm:flex-none min-w-0 min-h-[38px] px-2.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            <span class="text-[#6F6259]">s/d</span>
            <input type="date" name="to" required value="{{ $dateTo }}" class="flex-1 sm:flex-none min-w-0 min-h-[38px] px-2.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            <button type="submit" class="min-h-[38px] px-4 py-1.5 rounded-xl bg-[#3B2A20] text-white font-bold shrink-0">
                Filter
            </button>
        </form>
    </div>

    <!-- Core KPIs (Zero hard-coded numbers) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <x-kpi-card
            label="Total Pendapatan (Revenue)"
            :value="'Rp' . number_format($summary['total_revenue'], 0, ',', '.')"
            subtext="Tunai & QRIS (Dibayar/Selesai)"
            badge="{{ $periodLabel }}"
            :accent="true"
        />
        <x-kpi-card
            label="Penjualan Kotor (Subtotal)"
            :value="'Rp' . number_format($summary['gross_sales'], 0, ',', '.')"
            :subtext="'Diskon: Rp' . number_format($summary['total_discount'], 0, ',', '.')"
        />
        <x-kpi-card
            label="Transaksi Berhasil"
            :value="$summary['tx_count']"
            :subtext="'Rata-rata: Rp' . number_format($summary['aov'], 0, ',', '.') . ' / order'"
        />
        <x-kpi-card
            label="Penggunaan Promo & Reward"
            :value="$summary['promo_usage_count']"
            :subtext="'Repeat Orders: ' . $summary['repeat_orders_count'] . ' transaksi'"
        />
    </div>

    <!-- Revenue by Payment Method & Member Breakdown -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-white border border-[#E6DED2] min-w-0">
            <div class="text-xs uppercase font-bold text-[#6F6259]">Pendapatan Tunai (Kasir)</div>
            <div class="text-lg font-black text-[#1F1A17] mt-1 tabular-nums break-words">Rp{{ number_format($summary['tunai_revenue'], 0, ',', '.') }}</div>
            <div class="text-[11px] text-[#6F6259] mt-0.5">{{ $summary['tunai_count'] }} Transaksi</div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-[#E6DED2] min-w-0">
            <div class="text-xs uppercase font-bold text-[#6F6259]">Pendapatan QRIS</div>
            <div class="text-lg font-black text-[#1F1A17] mt-1 tabular-nums break-words">Rp{{ number_format($summary['qris_revenue'], 0, ',', '.') }}</div>
            <div class="text-[11px] text-[#6F6259] mt-0.5">{{ $summary['qris_count'] }} Transaksi</div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-[#E6DED2] min-w-0">
            <div class="text-xs uppercase font-bold text-[#6F6259]">Pelanggan Member vs Tamu</div>
            <div class="text-lg font-black text-[#1F1A17] mt-1 tabular-nums">{{ $summary['member_orders_count'] }} / {{ $summary['guest_orders_count'] }}</div>
            <div class="text-[11px] text-[#6F6259] mt-0.5">Order Member vs Guest</div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-[#E6DED2] min-w-0">
            <div class="text-xs uppercase font-bold text-[#6F6259]">Rata-rata Waktu Sajian</div>
            <div class="text-lg font-black text-[#1F1A17] mt-1 tabular-nums">{{ $summary['avg_prep_minutes'] }} Menit</div>
            <div class="text-[11px] text-[#6F6259] mt-0.5">Dibayar → Siap Diserahkan</div>
        </div>
    </div>

    <!-- RFM Segmentation & Top Products Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <!-- RFM Segments Summary -->
        <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#F2ECE1] pb-3">
                <div class="min-w-0">
                    <h2 class="text-base font-extrabold text-[#1F1A17]">Distribusi Segmen RFM Pelanggan</h2>
                    <p class="text-xs text-[#6F6259]">Hasil segmentasi otomatis berbasis skor Recency, Frequency, Monetary.</p>
                </div>
                <a href="{{ route('manager.rfm.index') }}" class="min-h-[38px] inline-flex items-center px-3 py-1.5 rounded-xl bg-[#3B2A20] text-white text-xs font-bold whitespace-nowrap shrink-0">
                    Kelola RFM →
                </a>
            </div>

            @if(empty($rfmDistribution))
                <p class="text-xs text-[#6F6259] py-4 text-center">Belum ada analisis RFM tersimpan.</p>
            @else
                <div class="space-y-2.5 text-xs">
                    @foreach($rfmDistribution as $seg)
                        <div class="p-3 rounded-xl bg-[#FAF6EF] border border-[#E6DED2] flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <span class="font-extrabold text-sm text-[#1F1A17] break-words">{{ $seg['segment_name'] }}</span>
                                <div class="text-[#6F6259] tabular-nums">Total Belanja: Rp{{ number_format($seg['total_monetary'], 0, ',', '.') }}</div>
                            </div>
                            <span class="px-3 py-1 rounded-full bg-[#A9552F] text-white font-extrabold text-xs tabular-nums whitespace-nowrap shrink-0">
                                {{ $seg['member_count'] }} Member
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Top Selling Products -->
        <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#F2ECE1] pb-3">
                <div class="min-w-0">
                    <h2 class="text-base font-extrabold text-[#1F1A17]">Menu Terlaris (Top Selling)</h2>
                    <p class="text-xs text-[#6F6259]">Berdasarkan volume porsi terjual pada periode ini.</p>
                </div>
                <a href="{{ route('manager.reports.index') }}" class="text-xs font-bold text-[#A9552F] hover:underline whitespace-nowrap shrink-0">
                    Laporan Lengkap →
                </a>
            </div>

            @if(empty($topProducts))
                <p class="text-xs text-[#6F6259] py-4 text-center">Belum ada data penjualan pada periode ini.</p>
            @else
                <div class="divide-y divide-[#F2ECE1] text-xs">
                    @foreach($topProducts as $idx => $prod)
                        <div class="py-2.5 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-6 h-6 rounded-full bg-[#F2ECE1] text-[#3B2A20] flex items-center justify-center font-extrabold text-xs shrink-0">
                                    {{ $idx + 1 }}
                                </span>
                                <div class="min-w-0">
                                    <div class="font-bold text-[#1F1A17] break-words">{{ $prod['product_name'] }}</div>
                                    <div class="text-[#6F6259] tabular-nums">Penjualan: Rp{{ number_format($prod['total_sales'], 0, ',', '.') }}</div>
                                </div>
                            </div>
                            <div class="font-black text-sm text-[#3B2A20] tabular-nums whitespace-nowrap shrink-0">
                                {{ $prod['total_qty'] }} Porsi
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
