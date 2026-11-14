@extends('layouts.staff', ['title' => 'Pesanan Masuk POS'])

@section('heading', 'Kasir POS — Papan Pesanan Masuk')

@section('content')
<div class="space-y-4">
    <!-- Filter and Search Bar -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <a href="{{ route('pos.orders.index') }}"
               class="px-3 py-1.5 rounded-xl font-bold {{ empty($statusFilter) ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Semua Aktif
            </a>
            <a href="{{ route('pos.orders.index', ['status' => 'Menunggu Pembayaran']) }}"
               class="px-3 py-1.5 rounded-xl font-bold {{ $statusFilter === 'Menunggu Pembayaran' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Menunggu Pembayaran
            </a>
            <a href="{{ route('pos.orders.index', ['status' => 'Dibayar']) }}"
               class="px-3 py-1.5 rounded-xl font-bold {{ $statusFilter === 'Dibayar' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Dibayar
            </a>
            <a href="{{ route('pos.orders.index', ['status' => 'Diproses']) }}"
               class="px-3 py-1.5 rounded-xl font-bold {{ $statusFilter === 'Diproses' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Diproses KDS
            </a>
            <a href="{{ route('pos.orders.index', ['status' => 'Siap']) }}"
               class="px-3 py-1.5 rounded-xl font-bold {{ $statusFilter === 'Siap' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Siap Diserahkan
            </a>
        </div>

        <form method="GET" action="{{ route('pos.orders.index') }}" class="flex items-center gap-2">
            @if($statusFilter)
                <input type="hidden" name="status" value="{{ $statusFilter }}">
            @endif
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari No. Order / Nama..."
                   class="px-3.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs">
            <button type="submit" class="px-3 py-1.5 rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-bold">
                Cari
            </button>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3">No. Transaksi</th>
                        <th class="px-4 py-3">Sumber / Meja</th>
                        <th class="px-4 py-3">Pelanggan</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Metode</th>
                        <th class="px-4 py-3 text-right">Total Tagihan</th>
                        <th class="px-4 py-3 text-center">Aksi Operasional</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($orders as $order)
                        <tr class="hover:bg-[#FAF6EF]/75 transition">
                            <td class="px-4 py-3.5">
                                <a href="{{ route('pos.orders.show', ['order' => $order->order_number]) }}" class="font-mono font-bold text-[#1F1A17] hover:text-[#A9552F]">
                                    {{ $order->order_number }}
                                </a>
                                <div class="text-[11px] text-[#6F6259] font-mono mt-0.5">Kode: <strong>{{ $order->order_code }}</strong></div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="font-bold text-[#1F1A17]">{{ $order->table?->label ?? 'Takeaway' }}</span>
                                <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] uppercase font-bold {{ $order->source === 'pos' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $order->source }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="font-bold text-[#1F1A17]">{{ $order->customer_name }}</div>
                                @if($order->customer_email)
                                    <div class="text-[11px] text-[#6F6259] truncate max-w-[140px]">{{ $order->customer_email }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                <x-status-badge :status="$order->status" />
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="font-bold uppercase text-[11px] text-[#3B2A20]">
                                    {{ $order->payment_method?->value ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-extrabold text-[#1F1A17] tabular-nums">
                                {{ $order->formattedTotal() }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('pos.orders.show', ['order' => $order->order_number]) }}" class="px-3 py-1.5 rounded-lg border border-[#D6CBBC] bg-white font-bold hover:bg-[#F2ECE1]">
                                        Buka
                                    </a>
                                    @if($order->status->value === 'Menunggu Pembayaran')
                                        <form method="POST" action="{{ route('pos.orders.confirm_cash', ['order' => $order->order_number]) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#1F7A4C] text-white font-extrabold hover:bg-emerald-800">
                                                Lunas Tunai
                                            </button>
                                        </form>
                                    @elseif($order->status->value === 'Siap')
                                        <form method="POST" action="{{ route('pos.orders.handover', ['order' => $order->order_number]) }}" class="flex items-center gap-1">
                                            @csrf
                                            <input type="hidden" name="order_code" value="{{ $order->order_code }}">
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#3B2A20] text-white font-bold">
                                                Serahkan
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-[#6F6259]">
                                Tidak ada data pesanan yang sesuai dengan filter ini.
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
