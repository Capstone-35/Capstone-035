@extends('layouts.staff', ['title' => 'Direktori Member'])

@section('heading', 'Manager — Direktori Member & Segmentasi RFM')

@section('content')
<div class="space-y-4">
    <!-- Filter -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('manager.members.index') }}"
               class="px-3 py-1.5 rounded-xl font-bold {{ empty($segmentFilter) ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Semua Segmen
            </a>
            @foreach($segments as $s)
                <a href="{{ route('manager.members.index', ['segment' => $s->name]) }}"
                   class="px-3 py-1.5 rounded-xl font-bold {{ $segmentFilter === $s->name ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                    {{ $s->name }}
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('manager.members.index') }}" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari Kode / Nama / Email..."
                   class="px-3.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs">
            <button type="submit" class="px-3 py-1.5 rounded-xl bg-[#3B2A20] text-white font-bold">
                Cari
            </button>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3">Kode Member</th>
                        <th class="px-4 py-3">Nama Pelanggan</th>
                        <th class="px-4 py-3">Kontak</th>
                        <th class="px-4 py-3">Segmen RFM</th>
                        <th class="px-4 py-3 text-right">Saldo Poin</th>
                        <th class="px-4 py-3 text-center">Tx Berhasil</th>
                        <th class="px-4 py-3 text-right">Total Belanja</th>
                        <th class="px-4 py-3 text-center">Profil</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($members as $m)
                        <tr class="hover:bg-[#FAF6EF]/60 transition">
                            <td class="px-4 py-3.5 font-mono font-bold text-[#A9552F]">{{ $m->member_code }}</td>
                            <td class="px-4 py-3.5 font-bold text-[#1F1A17]">{{ $m->user?->name }}</td>
                            <td class="px-4 py-3.5 text-[#6F6259]">
                                <div>{{ $m->user?->email }}</div>
                                <div class="text-[11px]">{{ $m->user?->phone }}</div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 font-bold text-[11px]">
                                    {{ $m->latestRfmResult?->segment_name ?? 'Belum Aktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-extrabold text-[#1F7A4C] tabular-nums">
                                {{ number_format($m->points_balance, 0, ',', '.') }} Poin
                            </td>
                            <td class="px-4 py-3.5 text-center font-bold tabular-nums">
                                {{ $m->successful_orders_count }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-extrabold tabular-nums">
                                Rp{{ number_format($m->total_spent ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <a href="{{ route('manager.members.show', ['member' => $m->id]) }}" class="px-3 py-1 rounded-lg border border-[#D6CBBC] bg-white font-bold hover:bg-[#F2ECE1]">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-[#6F6259]">
                                Tidak ada data member yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-[#E6DED2]">
            {{ $members->links() }}
        </div>
    </div>
</div>
@endsection
