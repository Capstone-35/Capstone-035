@extends('layouts.staff', ['title' => 'Analisis RFM'])

@section('heading', 'Manager — Behavioral RFM Analysis Engine (Module I)')

@section('content')
<div class="space-y-6">
    <!-- Header Summary & Recalculate Trigger -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0">
            <span class="text-xs font-bold uppercase tracking-wider text-[#A9552F]">RFM Analysis Engine (NFR-17)</span>
            <h2 class="text-base sm:text-lg font-extrabold text-[#1F1A17] mt-0.5">Analisis Skor Recency, Frequency, Monetary</h2>
            <div class="text-xs text-[#6F6259]">
                Analisis Terakhir: <strong>{{ $latestAnalysis->analysis_date?->format('d M Y') }}</strong> (Periode: {{ $latestAnalysis->period_days }} Hari) • Dievaluasi: <strong>{{ $latestAnalysis->members_evaluated }} Member</strong>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <a href="{{ route('manager.rfm.parameters') }}" class="flex-1 sm:flex-none min-h-[42px] inline-flex items-center justify-center px-4 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs font-bold text-[#3B2A20] hover:bg-[#E6DED2] whitespace-nowrap">
                Pengaturan Parameter Skor
            </a>
            <form method="POST" action="{{ route('manager.rfm.recalculate') }}" class="flex-1 sm:flex-none">
                @csrf
                <button type="submit" class="w-full min-h-[42px] px-4 sm:px-5 py-2 rounded-xl bg-[#3B2A20] text-white text-xs font-extrabold hover:bg-[#2A1E17] transition shadow-sm whitespace-nowrap">
                    ⚡ Hitung Ulang Skor RFM
                </button>
            </form>
        </div>
    </div>

    <!-- Segment Distribution Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
        @foreach($segments as $s)
            @php
                $distRow = collect($distribution)->firstWhere('segment_name', $s->name);
                $count = $distRow['member_count'] ?? 0;
            @endphp
            <a href="{{ route('manager.rfm.segment', ['segment' => $s->slug]) }}"
               class="p-3.5 sm:p-4 rounded-xl border border-[#E6DED2] bg-white hover:border-[#A9552F] transition flex flex-col justify-between min-w-0">
                <div>
                    <div class="font-extrabold text-xs text-[#1F1A17] break-words">{{ $s->name }}</div>
                    <div class="text-[10px] text-[#6F6259] mt-0.5 line-clamp-2">{{ $s->description }}</div>
                </div>
                <div class="mt-3 flex items-baseline justify-between gap-1">
                    <span class="text-xl font-black text-[#A9552F] tabular-nums">{{ $count }}</span>
                    <span class="text-[10px] text-[#6F6259]">Member</span>
                </div>
            </a>
        @endforeach
    </div>

    <!-- RFM Results Table -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden space-y-3">
        <div class="p-4 border-b border-[#E6DED2] flex flex-wrap items-center justify-between gap-2.5">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">Hasil Skor & Klasifikasi Seluruh Member</h3>
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                <a href="{{ route('manager.rfm.index') }}" class="min-h-[34px] inline-flex items-center px-3 py-1 rounded-lg whitespace-nowrap {{ empty($segmentFilter) ? 'bg-[#3B2A20] text-white font-bold' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                    Semua Segmen
                </a>
                @foreach($segments as $sg)
                    <a href="{{ route('manager.rfm.index', ['segment' => $sg->name]) }}" class="min-h-[34px] inline-flex items-center px-3 py-1 rounded-lg whitespace-nowrap {{ $segmentFilter === $sg->name ? 'bg-[#3B2A20] text-white font-bold' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                        {{ $sg->name }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[740px] text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3">Member</th>
                        <th class="px-4 py-3 whitespace-nowrap">Segmen Hasil</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Recency (R)</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Frequency (F)</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Monetary (M)</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Skor RFM</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($results as $res)
                        <tr class="hover:bg-[#FAF6EF]/60 transition">
                            <td class="px-4 py-3">
                                <div class="font-bold text-[#1F1A17]">{{ $res->member?->user?->name }}</div>
                                <div class="font-mono text-[11px] text-[#A9552F]">{{ $res->member?->member_code }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 font-bold text-[11px] whitespace-nowrap">
                                    {{ $res->segment_name }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div>{{ $res->recency_days !== null ? $res->recency_days . ' hari' : '—' }}</div>
                                <span class="font-bold text-[#A9552F]">Skor: {{ $res->r_score }}</span>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div>{{ $res->frequency }}x order</div>
                                <span class="font-bold text-[#A9552F]">Skor: {{ $res->f_score }}</span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="font-mono font-bold tabular-nums">Rp{{ number_format($res->monetary, 0, ',', '.') }}</div>
                                <span class="font-bold text-[#A9552F]">Skor: {{ $res->m_score }}</span>
                            </td>
                            <td class="px-4 py-3 text-center font-mono font-black text-sm text-[#3B2A20] whitespace-nowrap">
                                {{ $res->r_score }}{{ $res->f_score }}{{ $res->m_score }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <a href="{{ route('manager.members.show', ['member' => $res->member_id]) }}" class="min-h-[34px] inline-flex items-center px-2.5 py-1 rounded-lg border border-[#D6CBBC] font-bold hover:bg-[#F2ECE1]">
                                    Profil
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-[#6F6259]">
                                Tidak ada data hasil RFM untuk filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-[#E6DED2]">
            {{ $results->links() }}
        </div>
    </div>
</div>
@endsection
