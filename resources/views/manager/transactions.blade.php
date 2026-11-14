@extends('layouts.staff', ['title' => 'Eksplorasi Transaksi'])

@section('heading', 'Manager — Eksplorasi Transaksi & Otoritas Pembatalan')

@section('content')
<div class="space-y-4">
    <!-- Filter -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm">
        <form method="GET" action="{{ route('manager.transactions.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 text-xs">
            <div>
                <label class="block text-[10px] font-bold uppercase text-[#6F6259] mb-1">Dari Tanggal</label>
                <input type="date" name="from" value="{{ $dateFrom }}" class="w-full px-2.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-[#6F6259] mb-1">Sampai Tanggal</label>
                <input type="date" name="to" value="{{ $dateTo }}" class="w-full px-2.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-[#6F6259] mb-1">Status</label>
                <select name="status" class="w-full px-2.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
                    <option value="">Semua Status</option>
                    @foreach(\App\Enums\OrderStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ $status === $st->value ? 'selected' : '' }}>{{ $st->value }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-[#6F6259] mb-1">Metode</label>
                <select name="payment_method" class="w-full px-2.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
                    <option value="">Semua Metode</option>
                    <option value="tunai" {{ $method === 'tunai' ? 'selected' : '' }}>Tunai</option>
                    <option value="qris" {{ $method === 'qris' ? 'selected' : '' }}>QRIS</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-[#6F6259] mb-1">Pencarian</label>
                <input type="text" name="q" value="{{ $search }}" placeholder="No. Order / Nama..."
                       class="w-full px-2.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div class="pt-4 flex items-end">
                <button type="submit" class="w-full min-h-[38px] rounded-xl bg-[#3B2A20] text-white font-bold">
                    Terapkan
                </button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3">No. Transaksi</th>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Pelanggan</th>
                        <th class="px-4 py-3">Sumber / Meja</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Metode</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-center">Aksi Manager</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($orders as $ord)
                        <tr class="hover:bg-[#FAF6EF]/60 transition">
                            <td class="px-4 py-3 font-mono font-bold text-[#1F1A17]">{{ $ord->order_number }}</td>
                            <td class="px-4 py-3 text-[#6F6259]">{{ $ord->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <div class="font-bold text-[#1F1A17]">{{ $ord->customer_name }}</div>
                                @if($ord->member)
                                    <span class="text-[10px] font-mono text-[#A9552F]">MBR: {{ $ord->member->member_code }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[#6F6259]">{{ $ord->table?->label ?? 'Takeaway' }} ({{ strtoupper($ord->source) }})</td>
                            <td class="px-4 py-3"><x-status-badge :status="$ord->status" /></td>
                            <td class="px-4 py-3 font-bold uppercase text-[11px] text-[#3B2A20]">{{ $ord->payment_method?->value ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-extrabold text-[#1F1A17] tabular-nums">{{ $ord->formattedTotal() }}</td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('manager.transactions.show', ['order' => $ord->order_number]) }}" class="px-3 py-1.5 rounded-lg border border-[#D6CBBC] bg-white font-bold hover:bg-[#F2ECE1]">
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
