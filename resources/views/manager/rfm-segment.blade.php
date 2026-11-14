@extends('layouts.staff', ['title' => 'Segmen ' . $segment->name])

@section('heading', 'Manager — Member Segmen RFM: ' . $segment->name)

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div>
                <a href="{{ route('manager.rfm.index') }}" class="text-xs font-bold text-[#6F6259] hover:text-[#3B2A20]">
                    ← Kembali ke Ikhtisar RFM
                </a>
                <h2 class="text-xl font-extrabold text-[#1F1A17] mt-1">{{ $segment->name }}</h2>
                <p class="text-xs text-[#6F6259]">{{ $segment->description }}</p>
            </div>
            <span class="px-3 py-1.5 rounded-full bg-amber-100 text-amber-900 font-extrabold text-xs">
                Prioritas Evaluasi #{{ $segment->priority }}
            </span>
        </div>

        @if(!empty($targetedPromos) && $targetedPromos->isNotEmpty())
            <div class="pt-3 border-t border-[#F2ECE1]">
                <h4 class="text-xs font-bold uppercase text-[#A9552F] mb-1.5">Promosi yang Menargetkan Segmen Ini:</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach($targetedPromos as $tp)
                        <a href="{{ route('manager.promotions.show', ['promotion' => $tp->id]) }}"
                           class="px-3 py-1 rounded-lg bg-[#FAF6EF] border border-[#E6DED2] text-xs font-bold text-[#3B2A20] hover:bg-[#F2ECE1]">
                            {{ $tp->name }} ({{ $tp->code }})
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Members in this segment -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3">Member</th>
                        <th class="px-4 py-3">Kontak</th>
                        <th class="px-4 py-3 text-center">Recency</th>
                        <th class="px-4 py-3 text-center">Frequency</th>
                        <th class="px-4 py-3 text-right">Monetary</th>
                        <th class="px-4 py-3 text-center">Skor RFM</th>
                        <th class="px-4 py-3 text-center">Profil</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($results as $res)
                        <tr class="hover:bg-[#FAF6EF]/60 transition">
                            <td class="px-4 py-3">
                                <div class="font-bold text-[#1F1A17]">{{ $res->member?->user?->name }}</div>
                                <div class="font-mono text-[11px] text-[#A9552F]">{{ $res->member?->member_code }}</div>
                            </td>
                            <td class="px-4 py-3 text-[#6F6259]">
                                <div>{{ $res->member?->user?->email }}</div>
                                <div class="text-[11px]">{{ $res->member?->user?->phone }}</div>
                            </td>
                            <td class="px-4 py-3 text-center font-bold">{{ $res->recency_days ?? '—' }} Hari (R={{ $res->r_score }})</td>
                            <td class="px-4 py-3 text-center font-bold">{{ $res->frequency }} Order (F={{ $res->f_score }})</td>
                            <td class="px-4 py-3 text-right font-extrabold tabular-nums">Rp{{ number_format($res->monetary, 0, ',', '.') }} (M={{ $res->m_score }})</td>
                            <td class="px-4 py-3 text-center font-mono font-black text-sm text-[#3B2A20]">{{ $res->r_score }}{{ $res->f_score }}{{ $res->m_score }}</td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('manager.members.show', ['member' => $res->member_id]) }}" class="px-2.5 py-1 rounded-lg border border-[#D6CBBC] font-bold">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-[#6F6259]">
                                Belum ada member yang terklasifikasi ke segmen {{ $segment->name }} pada periode ini.
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
