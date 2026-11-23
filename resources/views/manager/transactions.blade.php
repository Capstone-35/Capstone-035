@extends('layouts.staff', ['title' => 'Eksplorasi Transaksi'])

@section('heading', 'Manager — Eksplorasi Transaksi & Otoritas Pembatalan')

@section('content')
<div class="space-y-4">
    <!-- Filter -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm">
        <form method="GET" action="{{ route('manager.transactions.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 items-end text-xs">
            <div>
                <label class="block text-[10px] font-bold uppercase text-[#6F6259] mb-1">Dari Tanggal</label>
                <input type="date" name="from" value="{{ $dateFrom }}" class="w-full min-h-[40px] px-2.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-[#6F6259] mb-1">Sampai Tanggal</label>
                <input type="date" name="to" value="{{ $dateTo }}" class="w-full min-h-[40px] px-2.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-[#6F6259] mb-1">Status</label>
                <select name="status" class="w-full min-h-[40px] px-2.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
                    <option value="">Semua Status</option>
                    @foreach(\App\Enums\OrderStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ $status === $st->value ? 'selected' : '' }}>{{ $st->value }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-[#6F6259] mb-1">Metode</label>
                <select name="payment_method" class="w-full min-h-[40px] px-2.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
                    <option value="">Semua Metode</option>
                    <option value="tunai" {{ $method === 'tunai' ? 'selected' : '' }}>Tunai</option>
                    <option value="qris" {{ $method === 'qris' ? 'selected' : '' }}>QRIS</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-[#6F6259] mb-1">Pencarian</label>
                <input type="text" name="q" value="{{ $search }}" placeholder="No. Order / Nama..."
                       class="w-full min-h-[40px] px-2.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div class="flex items-center gap-1.5">
                <button type="submit" class="flex-1 min-h-[40px] rounded-xl bg-[#3B2A20] text-white font-bold hover:bg-[#2A1E17] transition">
                    Terapkan
                </button>
                <a href="{{ route('manager.transactions.index') }}" class="min-h-[40px] px-3 inline-flex items-center justify-center rounded-xl border border-[#D6CBBC] bg-white text-[#6F6259] font-bold hover:bg-[#F2ECE1]">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3 whitespace-nowrap">No. Transaksi</th>
                        <th class="px-4 py-3 whitespace-nowrap">Waktu</th>
                        <th class="px-4 py-3">Pelanggan</th>
                        <th class="px-4 py-3 whitespace-nowrap">Sumber / Meja</th>
                        <th class="px-4 py-3 whitespace-nowrap">Status</th>
                        <th class="px-4 py-3 whitespace-nowrap">Metode</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Total</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Aksi Manager</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($orders as $ord)
                        <tr class="hover:bg-[#FAF6EF]/60 transition">
                            <td class="px-4 py-3 font-mono font-bold text-[#1F1A17] whitespace-nowrap">{{ $ord->order_number }}</td>
                            <td class="px-4 py-3 text-[#6F6259] whitespace-nowrap">{{ $ord->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <div class="font-bold text-[#1F1A17]">{{ $ord->customer_name }}</div>
                                @if($ord->member)
                                    <span class="text-[10px] font-mono text-[#A9552F]">MBR: {{ $ord->member->member_code }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[#6F6259] whitespace-nowrap">{{ $ord->table?->label ?? 'Takeaway' }} ({{ strtoupper($ord->source) }})</td>
                            <td class="px-4 py-3 whitespace-nowrap"><x-status-badge :status="$ord->status" /></td>
                            <td class="px-4 py-3 font-bold uppercase text-[11px] text-[#3B2A20] whitespace-nowrap">{{ $ord->payment_method?->value ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-extrabold text-[#1F1A17] tabular-nums whitespace-nowrap">{{ $ord->formattedTotal() }}</td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <a href="{{ route('manager.transactions.show', ['order' => $ord->order_number]) }}" class="min-h-[34px] inline-flex items-center px-3 py-1 rounded-lg border border-[#D6CBBC] bg-white font-bold hover:bg-[#F2ECE1]">
                                    Periksa
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-[#6F6259]">
                                Tidak ada data transaksi yang sesuai filter.
                            </td>
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
