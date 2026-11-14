@extends('layouts.staff', ['title' => 'Pelanggan & RFM Owner'])

@section('heading', 'Owner — Ringkasan Pelanggan & Segmentasi RFM (Read-Only)')

@section('content')
<div class="space-y-6">
    <!-- Summary Header -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-kpi-card
            label="Total Member Terdaftar"
            :value="$totalMembers"
            subtext="Pelanggan Loyal 774 HUB"
            :accent="true"
        />
        <x-kpi-card
            label="Total Segmen RFM Aktif"
            :value="$segments->count()"
            subtext="Champions, Loyal, At Risk, dll."
        />
        <x-kpi-card
            label="Analisis Terakhir"
            :value="$latestAnalysis?->analysis_date?->format('d M Y') ?? '—'"
            :subtext="'Evaluasi ' . ($latestAnalysis?->period_days ?? 90) . ' Hari Terakhir'"
        />
    </div>

    <!-- Segments Breakdown -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-4">
        <h3 class="text-base font-extrabold text-[#1F1A17]">Distribusi Segmen Pelanggan</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            @foreach($segments as $s)
                @php
                    $d = collect($rfmDistribution)->firstWhere('segment_name', $s->name);
                    $cnt = $d['member_count'] ?? 0;
                @endphp
                <div class="p-3.5 rounded-xl border border-[#E6DED2] bg-[#FAF6EF]">
                    <div class="font-extrabold text-xs text-[#1F1A17]">{{ $s->name }}</div>
                    <div class="mt-2 text-xl font-black text-[#A9552F] tabular-nums">{{ $cnt }} <span class="text-xs font-normal text-[#6F6259]">Member</span></div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Top Spending Members -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E6DED2]">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">20 Member dengan Belanja Tertinggi (Top Customers)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3">Member</th>
                        <th class="px-4 py-3">Segmen RFM</th>
                        <th class="px-4 py-3 text-right">Saldo Poin</th>
                        <th class="px-4 py-3 text-center">Transaksi Berhasil</th>
                        <th class="px-4 py-3 text-right">Total Belanja (Revenue)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @foreach($topMembers as $tm)
                        <tr class="hover:bg-[#FAF6EF]/60">
                            <td class="px-4 py-3.5">
                                <div class="font-bold text-[#1F1A17]">{{ $tm->user?->name }}</div>
                                <div class="font-mono text-[11px] text-[#A9552F]">{{ $tm->member_code }}</div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 font-bold text-[11px]">
                                    {{ $tm->latestRfmResult?->segment_name ?? 'Belum Aktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-extrabold text-[#1F7A4C] tabular-nums">
                                {{ number_format($tm->points_balance, 0, ',', '.') }} Poin
                            </td>
                            <td class="px-4 py-3.5 text-center font-bold tabular-nums">
                                {{ $tm->successful_orders_count }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-black text-[#1F1A17] tabular-nums">
                                Rp{{ number_format($tm->total_spent ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
