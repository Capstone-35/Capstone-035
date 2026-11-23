@extends('layouts.staff', ['title' => 'Detail Promosi ' . $promotion->name])

@section('heading', 'Manager — Detail Promosi: ' . $promotion->name)

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <a href="{{ route('manager.promotions.index') }}" class="text-xs font-bold text-[#6F6259] hover:text-[#3B2A20]">
                    ← Kembali ke Daftar Promosi
                </a>
                <h2 class="text-lg sm:text-xl font-extrabold text-[#1F1A17] mt-1">{{ $promotion->name }}</h2>
                <div class="text-xs text-[#6F6259] break-words">
                    Kode Promo: <strong class="font-mono text-[#A9552F] break-all">{{ $promotion->code }}</strong> • Aturan: <strong>{{ $promotion->rule_type?->label() ?? '—' }}</strong>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <span class="px-3 py-1 rounded-full text-xs font-bold whitespace-nowrap {{ $promotion->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">
                    {{ $promotion->is_active ? 'Status: Aktif' : 'Status: Nonaktif' }}
                </span>
                <a href="{{ route('manager.promotions.edit', ['promotion' => $promotion->id]) }}" class="px-3.5 py-1.5 rounded-xl border border-[#D6CBBC] text-xs font-bold hover:bg-[#F2ECE1]">
                    Ubah
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 text-xs pt-3 border-t border-[#F2ECE1]">
            <div class="p-3 rounded-xl bg-[#FAF6EF]">
                <span class="text-[#6F6259]">Benefit:</span>
                <div class="font-extrabold text-sm text-[#1F1A17] mt-0.5">{{ $promotion->benefitSummary() }}</div>
            </div>
            <div class="p-3 rounded-xl bg-[#FAF6EF]">
                <span class="text-[#6F6259]">Min. Transaksi:</span>
                <div class="font-extrabold text-sm text-[#1F1A17] mt-0.5 tabular-nums">Rp{{ number_format($promotion->min_transaction, 0, ',', '.') }}</div>
            </div>
            <div class="p-3 rounded-xl bg-[#FAF6EF]">
                <span class="text-[#6F6259]">Total Terpakai:</span>
                <div class="font-extrabold text-sm text-[#1F1A17] mt-0.5 tabular-nums">{{ $promotion->usages->count() }}x Transaksi</div>
            </div>
            <div class="p-3 rounded-xl bg-[#FAF6EF]">
                <span class="text-[#6F6259]">Masa Berlaku:</span>
                <div class="font-bold text-xs text-[#1F1A17] mt-0.5">{{ $promotion->starts_at?->format('d/m/Y') ?? '—' }} – {{ $promotion->ends_at?->format('d/m/Y') ?? '—' }}</div>
            </div>
        </div>
    </div>

    <!-- Usage History -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E6DED2]">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">Riwayat Penggunaan Promosi Ini</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[660px] text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3">Member</th>
                        <th class="px-4 py-3 whitespace-nowrap">No. Transaksi</th>
                        <th class="px-4 py-3 whitespace-nowrap">Waktu Digunakan</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Potongan Diskon</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Return Tracking (Rule 6)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($promotion->usages as $usg)
                        <tr class="hover:bg-[#FAF6EF]/60 transition">
                            <td class="px-4 py-3 font-bold text-[#1F1A17]">{{ $usg->member?->user?->name ?? 'Member' }}</td>
                            <td class="px-4 py-3 font-mono font-bold whitespace-nowrap">
                                @if($usg->order)
                                    <a href="{{ route('manager.transactions.show', ['order' => $usg->order->order_number]) }}" class="text-[#A9552F] hover:underline">
                                        {{ $usg->order->order_number }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[#6F6259] whitespace-nowrap">{{ $usg->used_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-right font-extrabold tabular-nums text-[#1F7A4C] whitespace-nowrap">
                                Rp{{ number_format($usg->discount_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if($usg->returnRecord && $usg->returnRecord->returned_order_id)
                                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                                        Kembali dalam {{ $usg->returnRecord->days_to_return }} Hari
                                    </span>
                                @else
                                    <span class="text-gray-400 text-[10px]">Belum Ada Repeat Order</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-[#6F6259]">Belum pernah digunakan oleh pelanggan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
