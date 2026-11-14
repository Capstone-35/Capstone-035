@extends('layouts.staff', ['title' => 'Manager Dashboard'])

@section('heading', 'Manager — Dashboard Kinerja & Analisis RFM')

@section('content')
<div class="space-y-6">
    <!-- Period Selector -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold uppercase text-[#6F6259]">Periode Analisis:</span>
            <a href="{{ route('manager.dashboard', ['period' => 'harian']) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-bold {{ $period === 'harian' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Hari Ini
            </a>
            <a href="{{ route('manager.dashboard', ['period' => 'mingguan']) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-bold {{ $period === 'mingguan' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                7 Hari Terakhir
            </a>
            <a href="{{ route('manager.dashboard', ['period' => 'bulanan']) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-bold {{ $period === 'bulanan' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                30 Hari Terakhir
            </a>
        </div>

        <form method="GET" action="{{ route('manager.dashboard') }}" class="flex items-center gap-2 text-xs">
            <input type="hidden" name="period" value="custom">
            <input type="date" name="from" value="{{ $dateFrom }}" class="px-2.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            <span class="text-[#6F6259]">s/d</span>
            <input type="date" name="to" value="{{ $dateTo }}" class="px-2.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            <button type="submit" class="px-3 py-1.5 rounded-xl bg-[#3B2A20] text-white font-bold">
                Filter
            </button>
        </form>
    </div>

    <!-- Core KPIs (Zero hard-coded numbers) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-white border border-[#E6DED2]">
            <div class="text-xs uppercase font-bold text-[#6F6259]">Pendapatan Tunai (Kasir)</div>
            <div class="text-lg font-black text-[#1F1A17] mt-1 tabular-nums">Rp{{ number_format($summary['tunai_revenue'], 0, ',', '.') }}</div>
            <div class="text-[11px] text-[#6F6259] mt-0.5">{{ $summary['tunai_count'] }} Transaksi</div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-[#E6DED2]">
            <div class="text-xs uppercase font-bold text-[#6F6259]">Pendapatan QRIS</div>
            <div class="text-lg font-black text-[#1F1A17] mt-1 tabular-nums">Rp{{ number_format($summary['qris_revenue'], 0, ',', '.') }}</div>
            <div class="text-[11px] text-[#6F6259] mt-0.5">{{ $summary['qris_count'] }} Transaksi</div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-[#E6DED2]">
            <div class="text-xs uppercase font-bold text-[#6F6259]">Pelanggan Member vs Tamu</div>
            <div class="text-lg font-black text-[#1F1A17] mt-1 tabular-nums">{{ $summary['member_orders_count'] }} / {{ $summary['guest_orders_count'] }}</div>
            <div class="text-[11px] text-[#6F6259] mt-0.5">Order Member vs Guest</div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-[#E6DED2]">
            <div class="text-xs uppercase font-bold text-[#6F6259]">Rata-rata Waktu Sajian</div>
            <div class="text-lg font-black text-[#1F1A17] mt-1 tabular-nums">{{ $summary['avg_prep_minutes'] }} Menit</div>
            <div class="text-[11px] text-[#6F6259] mt-0.5">Dibayar → Siap Diserahkan</div>
        </div>
    </div>

    <!-- RFM Segmentation & Top Products Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- RFM Segments Summary -->
        <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-[#F2ECE1] pb-3">
                <div>
                    <h2 class="text-base font-extrabold text-[#1F1A17]">Distribusi Segmen RFM Pelanggan</h2>
                    <p class="text-xs text-[#6F6259]">Hasil segmentasi otomatis berbasis skor Recency, Frequency, Monetary.</p>
                </div>
                <a href="{{ route('manager.rfm.index') }}" class="px-3 py-1.5 rounded-xl bg-[#3B2A20] text-white text-xs font-bold">
                    Kelola RFM →
                </a>
            </div>

            @if(empty($rfmDistribution))
                <p class="text-xs text-[#6F6259] py-4 text-center">Belum ada analisis RFM tersimpan.</p>
            @else
                <div class="space-y-2.5 text-xs">
                    @foreach($rfmDistribution as $seg)
                        <div class="p-3 rounded-xl bg-[#FAF6EF] border border-[#E6DED2] flex items-center justify-between gap-2">
                            <div>
                                <span class="font-extrabold text-sm text-[#1F1A17]">{{ $seg['segment_name'] }}</span>
                                <div class="text-[#6F6259]">Total Belanja: Rp{{ number_format($seg['total_monetary'], 0, ',', '.') }}</div>
                            </div>
                            <span class="px-3 py-1 rounded-full bg-[#A9552F] text-white font-extrabold text-xs tabular-nums">
                                {{ $seg['member_count'] }} Member
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Top Selling Products -->
        <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-[#F2ECE1] pb-3">
                <div>
                    <h2 class="text-base font-extrabold text-[#1F1A17]">Menu Terlaris (Top Selling)</h2>
                    <p class="text-xs text-[#6F6259]">Berdasarkan volume porsi terjual pada periode ini.</p>
                </div>
                <a href="{{ route('manager.reports.index') }}" class="text-xs font-bold text-[#A9552F] hover:underline">
                    Laporan Lengkap →
                </a>
            </div>

            @if(empty($topProducts))
                <p class="text-xs text-[#6F6259] py-4 text-center">Belum ada data penjualan pada periode ini.</p>
            @else
                <div class="divide-y divide-[#F2ECE1] text-xs">
                    @foreach($topProducts as $idx => $prod)
                        <div class="py-2.5 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-full bg-[#F2ECE1] text-[#3B2A20] flex items-center justify-center font-extrabold text-xs">
                                    {{ $idx + 1 }}
                                </span>
                                <div>
                                    <div class="font-bold text-[#1F1A17]">{{ $prod['product_name'] }}</div>
                                    <div class="text-[#6F6259]">Penjualan: Rp{{ number_format($prod['total_sales'], 0, ',', '.') }}</div>
                                </div>
                            </div>
                            <div class="font-black text-sm text-[#3B2A20] tabular-nums">
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
