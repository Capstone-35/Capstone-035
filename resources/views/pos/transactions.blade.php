@extends('layouts.staff', ['title' => 'Daftar Transaksi POS'])

@section('heading', 'Kasir POS — Riwayat Seluruh Transaksi')

@section('content')
<div class="space-y-4">
    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm">
        <form method="GET" action="{{ route('pos.transactions.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end text-xs">
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-bold uppercase text-[#6F6259] mb-1">Tanggal</label>
                <input type="date" name="date" value="{{ $dateFilter }}" class="w-full min-h-[40px] px-3 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-bold uppercase text-[#6F6259] mb-1">Status</label>
                <select name="status" class="w-full min-h-[40px] px-3 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
                    <option value="">Semua Status</option>
                    @foreach(\App\Enums\OrderStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ $statusFilter === $st->value ? 'selected' : '' }}>{{ $st->value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-bold uppercase text-[#6F6259] mb-1">Metode</label>
                <select name="payment_method" class="w-full min-h-[40px] px-3 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
                    <option value="">Semua Metode</option>
                    <option value="tunai" {{ $methodFilter === 'tunai' ? 'selected' : '' }}>Tunai</option>
                    <option value="qris" {{ $methodFilter === 'qris' ? 'selected' : '' }}>QRIS</option>
                </select>
            </div>
            <div class="lg:col-span-3">
                <label class="block text-[10px] font-bold uppercase text-[#6F6259] mb-1">Pencarian</label>
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari No. Order / Pelanggan..."
                       class="w-full min-h-[40px] px-3 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div class="sm:col-span-2 lg:col-span-3 flex flex-wrap items-center gap-2">
                <button type="submit" class="flex-1 min-h-[40px] px-4 py-2 rounded-xl bg-[#3B2A20] text-[#FAF6EF] font-bold hover:bg-[#2A1E17] transition whitespace-nowrap">
                    Terapkan Filter
                </button>
                <a href="{{ route('pos.transactions.index', ['date' => '']) }}" class="min-h-[40px] inline-flex items-center justify-center px-3 py-2 rounded-xl border border-[#D6CBBC] bg-white text-[#6F6259] font-bold hover:bg-[#F2ECE1] whitespace-nowrap">
                    Semua
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[780px] text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3 whitespace-nowrap">No. Transaksi</th>
                        <th class="px-4 py-3 whitespace-nowrap">Waktu</th>
                        <th class="px-4 py-3">Pelanggan</th>
                        <th class="px-4 py-3 whitespace-nowrap">Meja</th>
                        <th class="px-4 py-3 whitespace-nowrap">Status</th>
                        <th class="px-4 py-3 whitespace-nowrap">Metode</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Total</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($orders as $order)
                        <tr class="hover:bg-[#FAF6EF]/60 transition">
                            <td class="px-4 py-3 font-mono font-bold text-[#1F1A17] whitespace-nowrap">{{ $order->order_number }}</td>
                            <td class="px-4 py-3 text-[#6F6259] whitespace-nowrap">{{ $order->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 font-semibold text-[#1F1A17]">{{ $order->customer_name }}</td>
                            <td class="px-4 py-3 text-[#6F6259] whitespace-nowrap">{{ $order->table?->label ?? 'Takeaway' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><x-status-badge :status="$order->status" /></td>
                            <td class="px-4 py-3 font-bold uppercase text-[11px] text-[#3B2A20] whitespace-nowrap">{{ $order->payment_method?->value ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-extrabold text-[#1F1A17] tabular-nums whitespace-nowrap">{{ $order->formattedTotal() }}</td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <a href="{{ route('pos.orders.show', ['order' => $order->order_number]) }}" class="min-h-[34px] inline-flex items-center px-3 py-1 rounded-lg border border-[#D6CBBC] bg-white font-bold hover:bg-[#F2ECE1]">
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
