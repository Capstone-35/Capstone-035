@extends('layouts.customer', ['title' => 'Pelacakan Pesanan ' . $order->order_number])

@section('content')
@php
    $steps = [
        'Menunggu Pembayaran',
        'Dibayar',
        'Diproses',
        'Siap',
        'Selesai',
    ];
    $currentStatus = $order->status->value;
    $currentIndex = array_search($currentStatus, $steps, true);
@endphp

<div class="space-y-4">
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-[#6F6259]">Status Transaksi</div>
                <h1 class="text-lg font-extrabold font-mono text-[#1F1A17]">{{ $order->order_number }}</h1>
                <div class="text-xs text-[#6F6259] mt-0.5">
                    {{ $order->table?->label ?? 'POS / Takeaway' }} • Pemesan: <strong>{{ $order->customer_name }}</strong>
                </div>
            </div>
            <x-status-badge :status="$order->status" />
        </div>

        <!-- Prominent 4-char Handover Code (D3) -->
        <div class="mt-4 p-4 rounded-2xl bg-[#3B2A20] text-[#FAF6EF] flex items-center justify-between">
            <div>
                <div class="text-xs uppercase tracking-wider font-bold text-[#FAF6EF]/75">Kode Verifikasi Penyerahan</div>
                <div class="text-xs text-[#FAF6EF]/80 mt-0.5">Tunjukkan kode 4 karakter ini saat pesanan diserahkan</div>
            </div>
            <div class="px-4 py-2 rounded-xl bg-[#A9552F] text-white font-mono text-2xl font-extrabold tracking-widest">
                {{ $order->order_code }}
            </div>
        </div>

        <!-- Stepper -->
        @if($currentStatus === 'Dibatalkan')
            <div class="mt-4 p-4 rounded-xl bg-[#FEE2E2] border border-[#FCA5A5] text-[#991B1B] text-xs font-bold">
                Pesanan ini telah dibatalkan.
                @if($order->cancel_reason)
                    <div class="mt-1 font-normal">Alasan: {{ $order->cancel_reason }}</div>
                @endif
            </div>
        @else
            <div class="mt-5 grid grid-cols-5 gap-1.5 text-center">
                @foreach($steps as $idx => $stepName)
                    @php
                        $done = $currentIndex !== false && $idx <= $currentIndex;
                        $isCurrent = $stepName === $currentStatus;
                    @endphp
                    <div class="p-2 rounded-xl border text-[11px] font-bold {{ $isCurrent ? 'bg-[#A9552F] text-white border-[#A9552F]' : ($done ? 'bg-[#DCFCE7] text-[#166534] border-[#86EFAC]' : 'bg-[#FAF6EF] text-[#6F6259] border-[#E6DED2]') }}">
                        <div>{{ $idx + 1 }}</div>
                        <div class="mt-0.5 leading-tight">{{ $stepName }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="mt-5 pt-4 border-t border-[#E6DED2] flex flex-wrap items-center justify-between gap-2">
            @if($currentStatus === 'Menunggu Pembayaran')
                <a href="{{ route('order.payment', ['public_token' => $order->public_token]) }}"
                   class="min-h-[44px] px-5 py-2.5 inline-flex items-center rounded-xl bg-[#A9552F] text-white text-xs font-extrabold">
                    Selesaikan Pembayaran →
                </a>
                <form method="POST" action="{{ route('order.cancel.customer', ['public_token' => $order->public_token]) }}"
                      onsubmit="return confirm('Batalkan pesanan ini?');">
                    @csrf
                    <button type="submit" class="min-h-[44px] px-4 py-2 rounded-xl text-xs font-bold text-[#B91C1C] hover:bg-[#FEE2E2]">
                        Batalkan Pesanan
                    </button>
                </form>
            @endif

            @if($order->invoice)
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('order.invoice.show', ['public_token' => $order->public_token]) }}"
                       class="min-h-[44px] px-4 py-2.5 inline-flex items-center rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-bold">
                        Lihat Invoice Digital ({{ $order->invoice->invoice_number }})
                    </a>
                    <a href="{{ route('order.invoice.pdf', ['public_token' => $order->public_token]) }}"
                       class="min-h-[44px] px-4 py-2.5 inline-flex items-center rounded-xl border border-[#3B2A20] text-[#3B2A20] text-xs font-bold hover:bg-[#F2ECE1]">
                        Unduh PDF Invoice
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Order Items & Totals -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-3">
        <h2 class="text-sm font-extrabold text-[#1F1A17]">Rincian Item Pesanan</h2>
        <div class="divide-y divide-[#F2ECE1] text-xs">
            @foreach($order->items as $item)
                <div class="py-2.5 flex justify-between gap-2">
                    <div>
                        <span class="font-bold text-[#1F1A17]">{{ $item->quantity }}x {{ $item->product_name }}</span>
                        @if($item->note)
                            <div class="text-[#A9552F]">Catatan: "{{ $item->note }}"</div>
                        @endif
                    </div>
                    <span class="font-bold tabular-nums">Rp{{ number_format($item->line_total, 0, ',', '.') }}</span>
                </div>
            @endforeach
        </div>

        <div class="pt-3 border-t border-[#E6DED2] space-y-1.5 text-xs">
            <div class="flex justify-between text-[#6F6259]">
                <span>Subtotal</span>
                <span class="font-bold text-[#1F1A17] tabular-nums">Rp{{ number_format($order->subtotal, 0, ',', '.') }}</span>
            </div>
            @if($order->discount_amount > 0)
                <div class="flex justify-between text-[#1F7A4C]">
                    <span>Potongan Promo / Voucher</span>
                    <span class="font-bold tabular-nums">-Rp{{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                </div>
            @endif
            @if($order->tax_rate > 0)
                <div class="flex justify-between text-[#6F6259]">
                    <span>Pajak ({{ number_format($order->tax_rate, 0) }}%)</span>
                    <span class="font-bold text-[#1F1A17] tabular-nums">Rp{{ number_format($order->tax_amount, 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="pt-2 border-t border-[#E6DED2] flex justify-between text-base font-extrabold text-[#1F1A17]">
                <span>Total</span>
                <span class="tabular-nums">{{ $order->formattedTotal() }}</span>
            </div>
        </div>
    </div>

    <!-- Audit Timeline -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm">
        <h2 class="text-sm font-extrabold text-[#1F1A17] mb-3">Riwayat Status Pesanan</h2>
        <div class="space-y-2.5 text-xs">
            @foreach($order->statusHistories as $history)
                <div class="flex items-start justify-between gap-3 py-1.5 border-b border-[#FAF6EF] last:border-0">
                    <div>
                        <span class="font-bold text-[#1F1A17]">{{ $history->to_status }}</span>
                        @if($history->reason)
                            <span class="text-[#6F6259]">— {{ $history->reason }}</span>
                        @endif
                    </div>
                    <span class="text-[#6F6259] font-mono shrink-0">
                        {{ $history->created_at?->timezone('Asia/Jakarta')->format('H:i:s') }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</div>

@if(!in_array($currentStatus, ['Selesai', 'Dibatalkan'], true))
<script>
    const currentStatus = @json($currentStatus);
    setInterval(async () => {
        try {
            const res = await fetch("{{ route('order.status.json', ['public_token' => $order->public_token]) }}", {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.status && data.status !== currentStatus) {
                window.location.reload();
            }
        } catch (e) {}
    }, 4000);
</script>
@endif
@endsection
