@extends('layouts.staff', ['title' => 'Laporan Operasional'])

@section('heading', 'Manager — Laporan Penjualan & Pendapatan (FR-13, NFR-04)')

@section('content')
<div class="space-y-6">
    <!-- Filter and CSV Export Bar -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 lg:p-5 shadow-sm space-y-4 text-xs">
        <!-- Row 1: Quick Presets & Active Period Export -->
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3.5 border-b border-[#F2ECE1]">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[#6F6259] font-bold mr-1">Filter Cepat:</span>
                <a href="{{ route('manager.reports.index', ['period' => 'harian']) }}"
                   class="px-3 py-1.5 rounded-xl font-bold transition {{ $period === 'harian' ? 'bg-[#3B2A20] text-[#FAF6EF] shadow-xs' : 'bg-[#FAF6EF] text-[#6F6259] hover:bg-[#F2ECE1]' }}">
                    Harian (Hari Ini)
                </a>
                <a href="{{ route('manager.reports.index', ['period' => 'mingguan']) }}"
                   class="px-3 py-1.5 rounded-xl font-bold transition {{ $period === 'mingguan' ? 'bg-[#3B2A20] text-[#FAF6EF] shadow-xs' : 'bg-[#FAF6EF] text-[#6F6259] hover:bg-[#F2ECE1]' }}">
                    Mingguan (7 Hari)
                </a>
                <a href="{{ route('manager.reports.index', ['period' => 'bulanan']) }}"
                   class="px-3 py-1.5 rounded-xl font-bold transition {{ $period === 'bulanan' ? 'bg-[#3B2A20] text-[#FAF6EF] shadow-xs' : 'bg-[#FAF6EF] text-[#6F6259] hover:bg-[#F2ECE1]' }}">
                    Bulanan (30 Hari)
                </a>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <span class="px-3 py-1.5 rounded-xl bg-[#F2ECE1] border border-[#E6DED2] text-[#3B2A20] font-bold">
                    Periode Aktif: <strong class="text-[#A9552F]">{{ $periodLabel }}</strong>
                </span>
                <a href="{{ route('manager.reports.index', [
                        'period' => $period,
                        'month' => $month ?? null,
                        'month_from' => $monthFrom ?? null,
                        'month_to' => $monthTo ?? null,
                        'group_by' => $activeGroupBy ?? null,
                        'from' => $dateFrom,
                        'to' => $dateTo,
                        'format' => 'csv',
                    ]) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#1F7A4C] text-white font-extrabold hover:bg-emerald-800 transition shadow-xs">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Ekspor Laporan Aktif (CSV)</span>
                </a>
            </div>
        </div>

        <!-- Row 2: Specific Month Filter & Month Range Filter -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <!-- Box 1: Pilih Bulan Tertentu -->
            <form method="GET" action="{{ route('manager.reports.index') }}"
                  class="rounded-xl p-3.5 border transition {{ $period === 'per_bulan' ? 'bg-[#FAF6EF] border-[#A9552F] ring-1 ring-[#A9552F]/30' : 'bg-[#FAF6EF]/60 border-[#E6DED2]' }}">
                <input type="hidden" name="period" value="per_bulan">
                <div class="flex items-center justify-between gap-2 mb-2.5">
                    <div class="font-extrabold text-[#1F1A17] flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full {{ $period === 'per_bulan' ? 'bg-[#A9552F]' : 'bg-[#6F6259]/50' }}"></span>
                        <span>Filter Per Bulan (Pilih 1 Bulan)</span>
                    </div>
                    @if($period === 'per_bulan')
                        <span class="px-2 py-0.5 rounded-full bg-[#A9552F] text-white text-[10px] font-extrabold uppercase">Aktif</span>
                    @endif
                </div>

                <div class="flex flex-wrap items-end gap-2">
                    <div class="flex-1 min-w-[130px]">
                        <label for="single_month_num" class="block text-[11px] font-bold text-[#6F6259] mb-1">Bulan</label>
                        <select id="single_month_num" name="month_num"
                                class="w-full px-3 py-2 rounded-xl bg-white border border-[#D6CBBC] text-[#1F1A17] font-semibold focus:outline-none focus:border-[#A9552F]">
                            @foreach($availableMonths as $mNum => $mName)
                                <option value="{{ $mNum }}" @selected((int) $selectedMonthNum === (int) $mNum)>{{ $mName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-24">
                        <label for="single_year" class="block text-[11px] font-bold text-[#6F6259] mb-1">Tahun</label>
                        <select id="single_year" name="year"
                                class="w-full px-3 py-2 rounded-xl bg-white border border-[#D6CBBC] text-[#1F1A17] font-semibold focus:outline-none focus:border-[#A9552F]">
                            @foreach($availableYears as $yr)
                                <option value="{{ $yr }}" @selected((int) $selectedYear === (int) $yr)>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button type="submit"
                                class="px-3.5 py-2 rounded-xl bg-[#3B2A20] text-[#FAF6EF] font-bold hover:bg-[#2A1E17] transition cursor-pointer">
                            Tampilkan
                        </button>
                        <button type="submit" name="format" value="csv"
                                class="inline-flex items-center gap-1 px-3 py-2 rounded-xl bg-[#1F7A4C] text-white font-bold hover:bg-emerald-800 transition cursor-pointer"
                                title="Unduh CSV untuk bulan yang dipilih">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>CSV</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Box 2: Pilih Rentang Bulan (Dari Bulan s/d Bulan) -->
            <form method="GET" action="{{ route('manager.reports.index') }}"
                  class="rounded-xl p-3.5 border transition {{ $period === 'rentang_bulan' ? 'bg-[#FAF6EF] border-[#A9552F] ring-1 ring-[#A9552F]/30' : 'bg-[#FAF6EF]/60 border-[#E6DED2]' }}">
                <input type="hidden" name="period" value="rentang_bulan">
                <div class="flex items-center justify-between gap-2 mb-2.5">
                    <div class="font-extrabold text-[#1F1A17] flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full {{ $period === 'rentang_bulan' ? 'bg-[#A9552F]' : 'bg-[#6F6259]/50' }}"></span>
                        <span>Filter Rentang Bulan (Dari Bulan s/d Bulan)</span>
                    </div>
                    @if($period === 'rentang_bulan')
                        <span class="px-2 py-0.5 rounded-full bg-[#A9552F] text-white text-[10px] font-extrabold uppercase">Aktif</span>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mb-2.5">
                    <div>
                        <label class="block text-[11px] font-bold text-[#6F6259] mb-1">Dari Bulan</label>
                        <div class="flex gap-1.5">
                            <select name="month_from_num" aria-label="Dari Bulan"
                                    class="flex-1 px-2.5 py-2 rounded-xl bg-white border border-[#D6CBBC] text-[#1F1A17] font-semibold focus:outline-none focus:border-[#A9552F]">
                                @foreach($availableMonths as $mNum => $mName)
                                    <option value="{{ $mNum }}" @selected((int) $selectedMonthFromNum === (int) $mNum)>{{ $mName }}</option>
                                @endforeach
                            </select>
                            <select name="year_from" aria-label="Dari Tahun"
                                    class="w-20 px-2 py-2 rounded-xl bg-white border border-[#D6CBBC] text-[#1F1A17] font-semibold focus:outline-none focus:border-[#A9552F]">
                                @foreach($availableYears as $yr)
                                    <option value="{{ $yr }}" @selected((int) $selectedYearFrom === (int) $yr)>{{ $yr }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-[#6F6259] mb-1">Sampai Bulan</label>
                        <div class="flex gap-1.5">
                            <select name="month_to_num" aria-label="Sampai Bulan"
                                    class="flex-1 px-2.5 py-2 rounded-xl bg-white border border-[#D6CBBC] text-[#1F1A17] font-semibold focus:outline-none focus:border-[#A9552F]">
                                @foreach($availableMonths as $mNum => $mName)
                                    <option value="{{ $mNum }}" @selected((int) $selectedMonthToNum === (int) $mNum)>{{ $mName }}</option>
                                @endforeach
                            </select>
                            <select name="year_to" aria-label="Sampai Tahun"
                                    class="w-20 px-2 py-2 rounded-xl bg-white border border-[#D6CBBC] text-[#1F1A17] font-semibold focus:outline-none focus:border-[#A9552F]">
                                @foreach($availableYears as $yr)
                                    <option value="{{ $yr }}" @selected((int) $selectedYearTo === (int) $yr)>{{ $yr }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                    <div class="flex items-center gap-2">
                        <label for="range_group_by" class="text-[11px] font-bold text-[#6F6259]">Rincian Tabel:</label>
                        <select id="range_group_by" name="group_by"
                                class="px-2.5 py-1.5 rounded-lg bg-white border border-[#D6CBBC] text-[#1F1A17] font-semibold focus:outline-none focus:border-[#A9552F]">
                            <option value="bulanan" @selected(($activeGroupBy ?? 'bulanan') === 'bulanan')>Rekap Per Bulan</option>
                            <option value="harian" @selected(($activeGroupBy ?? 'bulanan') === 'harian')>Rincian Per Tanggal</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button type="submit"
                                class="px-3.5 py-2 rounded-xl bg-[#3B2A20] text-[#FAF6EF] font-bold hover:bg-[#2A1E17] transition cursor-pointer">
                            Tampilkan Rentang
                        </button>
                        <button type="submit" name="format" value="csv"
                                class="inline-flex items-center gap-1 px-3 py-2 rounded-xl bg-[#1F7A4C] text-white font-bold hover:bg-emerald-800 transition cursor-pointer"
                                title="Unduh CSV untuk rentang bulan yang dipilih">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>CSV</span>
                        </button>
                    </div>
                </div>
            </form>
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
        <div class="p-4 border-b border-[#E6DED2] flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">
                {{ ($activeGroupBy ?? 'harian') === 'bulanan' ? 'Rekapitulasi Per Bulan' : 'Rincian Per Tanggal / Periode' }}
            </h3>
            <span class="text-xs font-semibold text-[#6F6259]">{{ $periodLabel }}</span>
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
