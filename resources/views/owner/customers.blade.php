@extends('layouts.staff', ['title' => 'Pelanggan & RFM Owner'])

@section('heading', 'Owner — Ringkasan Pelanggan & Segmentasi RFM (Read-Only)')

@section('content')
<div class="space-y-6">
    <!-- Summary Header -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-kpi-card
            label="Total Member Terdaftar"
            :value="$totalMembers"
            subtext="Pelanggan Loyal 774 HUB"
            :accent="true"
        />
        <x-kpi-card
            label="Total Segmen RFM Aktif"
            :value="$segments->count()"
            subtext="Pelanggan Setia, Loyal, Berisiko, dll."
        />
        <x-kpi-card
            label="Analisis Terakhir"
            :value="$latestAnalysis?->analysis_date?->format('d M Y') ?? '—'"
            :subtext="'Evaluasi ' . ($latestAnalysis?->period_days ?? 90) . ' Hari Terakhir'"
        />
    </div>

    <!-- Segments Breakdown -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-4">
        <h3 class="text-base font-extrabold text-[#1F1A17]">Distribusi Segmen Pelanggan</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
            @foreach($segments as $s)
                @php
                    $d = collect($rfmDistribution)->firstWhere('segment_name', $s->name);
                    $cnt = $d['member_count'] ?? 0;
                @endphp
                <div class="p-3.5 rounded-xl border border-[#E6DED2] bg-[#FAF6EF] min-w-0">
                    <div class="font-extrabold text-xs text-[#1F1A17] truncate" title="{{ $s->name }}">{{ $s->name }}</div>
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
            <table class="w-full min-w-[600px] text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3">Member</th>
                        <th class="px-4 py-3 whitespace-nowrap">Segmen RFM</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Saldo Poin</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Transaksi Berhasil</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Total Belanja (Revenue)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($topMembers as $tm)
                        <tr class="hover:bg-[#FAF6EF]/60">
                            <td class="px-4 py-3.5">
                                <div class="font-bold text-[#1F1A17]">{{ $tm->user?->name ?? 'Member' }}</div>
                                <div class="font-mono text-[11px] text-[#A9552F]">{{ $tm->member_code }}</div>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 font-bold text-[11px] whitespace-nowrap">
                                    {{ $tm->latestRfmResult?->segment_name ?? 'Belum Aktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-extrabold text-[#1F7A4C] tabular-nums whitespace-nowrap">
                                {{ number_format($tm->points_balance, 0, ',', '.') }} Poin
                            </td>
                            <td class="px-4 py-3.5 text-center font-bold tabular-nums whitespace-nowrap">
                                {{ $tm->successful_orders_count }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-black text-[#1F1A17] tabular-nums whitespace-nowrap">
                                Rp{{ number_format($tm->total_spent ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-[#6F6259]">Belum ada data member terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
