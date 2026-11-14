@extends('layouts.staff', ['title' => 'Daftar Transaksi POS'])

@section('heading', 'Kasir POS — Riwayat Seluruh Transaksi')

@section('content')
<div class="space-y-4">
    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm">
        <form method="GET" action="{{ route('pos.transactions.index') }}" class="flex flex-wrap items-center gap-3 text-xs">
            <div>
                <label class="block text-[10px] font-bold uppercase text-[#6F6259] mb-1">Tanggal</label>
                <input type="date" name="date" value="{{ $dateFilter }}" class="px-3 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-[#6F6259] mb-1">Status</label>
                <select name="status" class="px-3 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
                    <option value="">Semua Status</option>
                    @foreach(\App\Enums\OrderStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ $statusFilter === $st->value ? 'selected' : '' }}>{{ $st->value }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-[#6F6259] mb-1">Metode</label>
                <select name="payment_method" class="px-3 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
                    <option value="">Semua Metode</option>
                    <option value="tunai" {{ $methodFilter === 'tunai' ? 'selected' : '' }}>Tunai</option>
                    <option value="qris" {{ $methodFilter === 'qris' ? 'selected' : '' }}>QRIS</option>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-[10px] font-bold uppercase text-[#6F6259] mb-1">Pencarian</label>
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari No. Order / Pelanggan..."
                       class="w-full px-3 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div class="pt-4">
                <button type="submit" class="px-4 py-2 rounded-xl bg-[#3B2A20] text-[#FAF6EF] font-bold">
                    Terapkan Filter
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
                        <th class="px-4 py-3">Meja</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Metode</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-center">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($orders as $order)
                        <tr class="hover:bg-[#FAF6EF]/60 transition">
                            <td class="px-4 py-3 font-mono font-bold text-[#1F1A17]">{{ $order->order_number }}</td>
                            <td class="px-4 py-3 text-[#6F6259]">{{ $order->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 font-semibold text-[#1F1A17]">{{ $order->customer_name }}</td>
                            <td class="px-4 py-3 text-[#6F6259]">{{ $order->table?->label ?? 'Takeaway' }}</td>
                            <td class="px-4 py-3"><x-status-badge :status="$order->status" /></td>
                            <td class="px-4 py-3 font-bold uppercase text-[11px] text-[#3B2A20]">{{ $order->payment_method?->value ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-extrabold text-[#1F1A17] tabular-nums">{{ $order->formattedTotal() }}</td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('pos.orders.show', ['order' => $order->order_number]) }}" class="px-3 py-1 rounded-lg border border-[#D6CBBC] bg-white font-bold hover:bg-[#F2ECE1]">
                                    Buka
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
