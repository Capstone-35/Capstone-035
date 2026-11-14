@extends('layouts.staff', ['title' => 'Verifikasi Pembayaran'])

@section('heading', 'Kasir POS — Verifikasi Pembayaran Tunai & QRIS')

@section('content')
<div class="space-y-6">
    <!-- Unpaid Queue -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-[#F2ECE1] pb-3">
            <div>
                <h2 class="text-base font-extrabold text-[#1F1A17]">Antrean Menunggu Pembayaran</h2>
                <p class="text-xs text-[#6F6259]">Verifikasi pembayaran tunai dari pelanggan atau pantau transaksi QRIS.</p>
            </div>
            <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-900 font-extrabold text-xs">
                {{ $pendingOrders->count() }} Antrean
            </span>
        </div>

        @if($pendingOrders->isEmpty())
            <p class="text-xs text-[#6F6259] py-6 text-center">Semua pesanan telah lunas atau belum ada antrean baru.</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($pendingOrders as $order)
                    <div class="p-4 rounded-xl border-2 {{ $order->payment_method?->value === 'tunai' ? 'border-amber-300 bg-amber-50/40' : 'border-blue-200 bg-blue-50/30' }} flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="font-mono font-extrabold text-sm text-[#1F1A17]">{{ $order->order_number }}</span>
                                    <div class="text-xs text-[#6F6259]">Meja: <strong>{{ $order->table?->label ?? 'Takeaway' }}</strong> • {{ $order->customer_name }}</div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[11px] font-extrabold uppercase {{ $order->payment_method?->value === 'tunai' ? 'bg-amber-200 text-amber-900' : 'bg-blue-100 text-blue-900' }}">
                                    {{ $order->payment_method?->value ?? 'Belum Pilih' }}
                                </span>
                            </div>

                            <div class="mt-3 text-xs text-[#1F1A17]">
                                @foreach($order->items as $it)
                                    <span class="inline-block mr-2">{{ $it->quantity }}x {{ $it->product_name }}</span>
                                @endforeach
                            </div>

                            <div class="mt-3 text-base font-extrabold text-[#3B2A20] tabular-nums">
                                Tagihan: {{ $order->formattedTotal() }}
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-[#E6DED2] flex flex-wrap items-center justify-between gap-2">
                            <form method="POST" action="{{ route('pos.orders.confirm_cash', ['order' => $order->order_number]) }}" class="flex items-center gap-1.5">
                                @csrf
                                <input type="number" name="cash_received" min="{{ $order->total_amount }}" value="{{ $order->total_amount }}"
                                       class="w-28 px-2 py-1.5 rounded-lg border border-[#D6CBBC] bg-white text-xs font-bold tabular-nums">
                                <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-[#1F7A4C] text-white text-xs font-extrabold hover:bg-emerald-800">
                                    Konfirmasi Tunai
                                </button>
                            </form>

                            <a href="{{ route('pos.orders.show', ['order' => $order->order_number]) }}" class="px-3 py-1.5 rounded-lg border border-[#D6CBBC] bg-white text-xs font-bold text-[#3B2A20]">
                                Buka
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Recently Paid Orders -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-3">
        <h2 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Transaksi yang Baru Dikonfirmasi Lunas</h2>
        <div class="divide-y divide-[#F2ECE1] text-xs">
            @foreach($recentPaidOrders as $pOrder)
                <div class="py-2.5 flex items-center justify-between gap-3">
                    <div>
                        <span class="font-mono font-bold text-[#1F1A17]">{{ $pOrder->order_number }}</span>
                        <span class="text-[#6F6259]">({{ $pOrder->table?->label ?? 'Takeaway' }} • {{ $pOrder->customer_name }})</span>
                        <span class="ml-2 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">
                            {{ $pOrder->payment_method?->value }}
                        </span>
                    </div>
                    <div class="text-right">
                        <span class="font-extrabold text-[#1F1A17] tabular-nums">{{ $pOrder->formattedTotal() }}</span>
                        <div class="text-[11px] text-[#6F6259]">{{ $pOrder->paid_at?->timezone('Asia/Jakarta')->format('H:i:s') }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
