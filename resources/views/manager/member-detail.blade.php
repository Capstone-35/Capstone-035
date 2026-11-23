@extends('layouts.staff', ['title' => 'Detail Member ' . $member->member_code])

@section('heading', 'Manager — Profil Member ' . $member->member_code)

@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('manager.members.index') }}" class="min-h-[38px] inline-flex items-center text-xs font-bold text-[#6F6259] hover:text-[#3B2A20]">
            ← Kembali ke Direktori Member
        </a>
    </div>

    <!-- Member Header -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <span class="text-xs font-bold uppercase tracking-wider text-[#A9552F]">Kode Member: {{ $member->member_code }}</span>
                <h2 class="text-lg sm:text-xl font-extrabold text-[#1F1A17] mt-0.5 break-words">{{ $member->user?->name }}</h2>
                <div class="text-xs text-[#6F6259] break-words">
                    Email: <span class="break-all">{{ $member->user?->email ?: '—' }}</span> • Telepon: {{ $member->user?->phone ?: '—' }} • Bergabung: {{ $member->joined_at?->timezone('Asia/Jakarta')->format('d M Y') }}
                </div>
            </div>
            <div class="text-left sm:text-right shrink-0">
                <span class="inline-block px-3 py-1.5 rounded-full bg-amber-100 text-amber-900 font-extrabold text-xs whitespace-nowrap">
                    Segmen: {{ $member->latestRfmResult?->segment_name ?? 'Belum Aktif' }}
                </span>
                <div class="text-lg font-black text-[#1F7A4C] tabular-nums mt-1">
                    {{ number_format($member->points_balance, 0, ',', '.') }} Poin
                </div>
            </div>
        </div>

        @if($member->latestRfmResult)
            <div class="mt-4 pt-4 border-t border-[#F2ECE1] grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-[#FAF6EF]">
                    <span class="text-[#6F6259]">Recency:</span>
                    <div class="font-bold text-sm text-[#1F1A17]">{{ $member->latestRfmResult->recency_days !== null ? $member->latestRfmResult->recency_days . ' Hari' : '—' }} (Skor {{ $member->latestRfmResult->r_score }})</div>
                </div>
                <div class="p-3 rounded-xl bg-[#FAF6EF]">
                    <span class="text-[#6F6259]">Frequency:</span>
                    <div class="font-bold text-sm text-[#1F1A17]">{{ $member->latestRfmResult->frequency }} Order (Skor {{ $member->latestRfmResult->f_score }})</div>
                </div>
                <div class="p-3 rounded-xl bg-[#FAF6EF]">
                    <span class="text-[#6F6259]">Monetary:</span>
                    <div class="font-bold text-sm text-[#1F1A17] tabular-nums">Rp{{ number_format($member->latestRfmResult->monetary, 0, ',', '.') }} (Skor {{ $member->latestRfmResult->m_score }})</div>
                </div>
                <div class="p-3 rounded-xl bg-[#FAF6EF]">
                    <span class="text-[#6F6259]">Skor RFM Gabungan:</span>
                    <div class="font-black text-sm text-[#A9552F]">{{ $member->latestRfmResult->r_score }}{{ $member->latestRfmResult->f_score }}{{ $member->latestRfmResult->m_score }}</div>
                </div>
            </div>
        @endif
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E6DED2]">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">Riwayat Pesanan Member</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3 whitespace-nowrap">No. Transaksi</th>
                        <th class="px-4 py-3 whitespace-nowrap">Waktu</th>
                        <th class="px-4 py-3 whitespace-nowrap">Status</th>
                        <th class="px-4 py-3 whitespace-nowrap">Metode</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Total</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($orders as $ord)
                        <tr class="hover:bg-[#FAF6EF]/60 transition">
                            <td class="px-4 py-3 font-mono font-bold whitespace-nowrap">{{ $ord->order_number }}</td>
                            <td class="px-4 py-3 text-[#6F6259] whitespace-nowrap">{{ $ord->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><x-status-badge :status="$ord->status" /></td>
                            <td class="px-4 py-3 uppercase font-bold text-[11px] text-[#3B2A20] whitespace-nowrap">{{ $ord->payment_method?->value ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-extrabold tabular-nums whitespace-nowrap">{{ $ord->formattedTotal() }}</td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <a href="{{ route('manager.transactions.show', ['order' => $ord->order_number]) }}" class="min-h-[34px] inline-flex items-center px-3 py-1 rounded-lg border border-[#D6CBBC] text-xs font-bold hover:bg-[#F2ECE1]">
                                    Buka
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-[#6F6259]">Belum ada pesanan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-[#E6DED2]">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
