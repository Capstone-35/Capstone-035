@extends('layouts.staff', ['title' => 'Verifikasi Pembayaran'])

@section('heading', 'Kasir POS — Verifikasi Pembayaran Tunai & QRIS')

@section('content')
<div class="space-y-6">
    <!-- Unpaid Queue -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#F2ECE1] pb-3">
            <div class="min-w-0">
                <h2 class="text-base font-extrabold text-[#1F1A17]">Antrean Menunggu Pembayaran</h2>
                <p class="text-xs text-[#6F6259]">Verifikasi pembayaran tunai dari pelanggan atau pantau transaksi QRIS.</p>
            </div>
            <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-900 font-extrabold text-xs whitespace-nowrap shrink-0">
                {{ $pendingOrders->count() }} Antrean
            </span>
        </div>

        @if($pendingOrders->isEmpty())
            <p class="text-xs text-[#6F6259] py-6 text-center">Semua pesanan telah lunas atau belum ada antrean baru.</p>
        @else
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                @foreach($pendingOrders as $order)
                    @php
                        $isQris = $order->payment_method?->value === 'qris';
                        $payStatus = $order->latestPayment?->status?->value;
                    @endphp
                    <div class="p-4 rounded-xl border-2 {{ !$isQris ? 'border-amber-300 bg-amber-50/40' : 'border-blue-200 bg-blue-50/30' }} flex flex-col justify-between">
                        <div>
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <span class="font-mono font-extrabold text-sm text-[#1F1A17] break-all">{{ $order->order_number }}</span>
                                    <div class="text-xs text-[#6F6259] break-words">Meja: <strong>{{ $order->table?->label ?? 'Takeaway' }}</strong> • {{ $order->customer_name }}</div>
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-extrabold uppercase whitespace-nowrap {{ !$isQris ? 'bg-amber-200 text-amber-900' : 'bg-blue-100 text-blue-900' }}">
                                        {{ $order->payment_method?->value ?? 'Belum Pilih' }}
                                    </span>
                                    @if($isQris && $payStatus)
                                        <x-status-badge :status="$payStatus" />
                                    @endif
                                </div>
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
                            <form method="POST" action="{{ route('pos.orders.confirm_cash', ['order' => $order->order_number]) }}"
                                  @if($isQris) onsubmit="return confirm('Pesanan ini menggunakan metode QRIS. Yakin alihkan & konfirmasi LUNAS secara TUNAI?');" @endif
                                  class="flex flex-wrap items-center gap-1.5 flex-1 min-w-0">
                                @csrf
                                <input type="number" name="cash_received" min="{{ $order->total_amount }}" value="{{ $order->total_amount }}"
                                       class="w-28 flex-1 sm:flex-none min-h-[40px] px-2.5 py-1.5 rounded-lg border border-[#D6CBBC] bg-white text-xs font-bold tabular-nums">
                                <button type="submit" class="min-h-[40px] px-3.5 py-1.5 rounded-lg bg-[#1F7A4C] text-white text-xs font-extrabold hover:bg-emerald-800 whitespace-nowrap transition">
                                    {{ $isQris ? 'Alihkan Tunai' : 'Konfirmasi Tunai' }}
                                </button>
                            </form>

                            <a href="{{ route('pos.orders.show', ['order' => $order->order_number]) }}" class="min-h-[40px] inline-flex items-center px-3 py-1.5 rounded-lg border border-[#D6CBBC] bg-white text-xs font-bold text-[#3B2A20] hover:bg-[#F2ECE1] shrink-0">
                                Buka
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Recently Paid Orders -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-3">
        <h2 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Transaksi yang Baru Dikonfirmasi Lunas</h2>
        <div class="divide-y divide-[#F2ECE1] text-xs">
            @forelse($recentPaidOrders as $pOrder)
                <div class="py-2.5 flex flex-wrap sm:flex-nowrap items-center justify-between gap-2.5">
                    <div class="min-w-0 flex flex-wrap items-center gap-x-2 gap-y-1">
                        <a href="{{ route('pos.orders.show', ['order' => $pOrder->order_number]) }}" class="font-mono font-bold text-[#1F1A17] hover:underline">{{ $pOrder->order_number }}</a>
                        <span class="text-[#6F6259]">({{ $pOrder->table?->label ?? 'Takeaway' }} • {{ $pOrder->customer_name }})</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800 whitespace-nowrap">
                            {{ $pOrder->payment_method?->value }}
                        </span>
                    </div>
                    <div class="text-left sm:text-right shrink-0">
                        <span class="font-extrabold text-[#1F1A17] tabular-nums whitespace-nowrap">{{ $pOrder->formattedTotal() }}</span>
                        <div class="text-[11px] text-[#6F6259] whitespace-nowrap">{{ $pOrder->paid_at?->timezone('Asia/Jakarta')->format('d/m H:i:s') }} WIB</div>
                    </div>
                </div>
            @empty
                <p class="text-xs text-[#6F6259] py-6 text-center">Belum ada transaksi yang baru dikonfirmasi lunas.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
