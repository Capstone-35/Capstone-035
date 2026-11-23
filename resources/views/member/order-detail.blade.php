@extends('layouts.customer', ['title' => 'Detail Transaksi ' . $order->order_number])

@section('content')
@php
    $taxRateFormatted = rtrim(rtrim(number_format((float) ($order->tax_rate ?? 0), 2, '.', ''), '0'), '.');
@endphp
<div class="max-w-3xl mx-auto space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('member.orders.index') }}" class="min-h-[42px] inline-flex items-center text-xs font-bold text-[#6F6259] hover:text-[#3B2A20]">
            ← Riwayat Transaksi
        </a>
        <form method="POST" action="{{ route('member.orders.repeat', ['order' => $order->order_number]) }}" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
            @csrf
            <button type="submit" class="min-h-[42px] px-4 py-2 rounded-xl bg-[#A9552F] text-white text-xs font-extrabold hover:bg-[#8E4423] transition">
                Pesan Lagi Item Ini →
            </button>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div class="min-w-0">
                <h1 class="text-base sm:text-lg font-extrabold font-mono text-[#1F1A17] break-all">{{ $order->order_number }}</h1>
                <div class="text-xs text-[#6F6259]">
                    {{ $order->created_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB • {{ $order->table?->label ?? 'POS' }}
                </div>
            </div>
            <x-status-badge :status="$order->status" />
        </div>

        @if($order->status->value === 'Dibatalkan' && $order->cancel_reason)
            <div class="p-3.5 rounded-xl bg-[#FEE2E2] border border-[#FCA5A5] text-xs text-[#991B1B]">
                <strong>Alasan Pembatalan:</strong> {{ $order->cancel_reason }}
            </div>
        @endif

        <div class="p-3.5 rounded-xl bg-[#FAF6EF] border border-[#E6DED2] flex flex-wrap items-center justify-between gap-2 text-xs">
            <span>Kode Verifikasi Penyerahan</span>
            <strong class="font-mono text-base text-[#3B2A20] tracking-widest">{{ $order->order_code }}</strong>
        </div>

        <div class="divide-y divide-[#F2ECE1] text-xs">
            @foreach($order->items as $item)
                <div class="py-2.5 flex justify-between gap-3">
                    <div class="min-w-0">
                        <span class="font-bold text-[#1F1A17] break-words">{{ $item->quantity }}x {{ $item->product_name }}</span>
                        @if($item->note)
                            <div class="text-[#A9552F] break-words">Catatan: "{{ $item->note }}"</div>
                        @endif
                    </div>
                    <span class="font-bold tabular-nums whitespace-nowrap shrink-0">Rp{{ number_format($item->line_total, 0, ',', '.') }}</span>
                </div>
            @endforeach
        </div>

        <div class="pt-3 border-t border-[#E6DED2] space-y-1.5 text-xs">
            <div class="flex justify-between gap-2 text-[#6F6259]">
                <span>Subtotal</span>
                <span class="font-bold text-[#1F1A17] tabular-nums whitespace-nowrap">Rp{{ number_format($order->subtotal, 0, ',', '.') }}</span>
            </div>
            @if($order->discount_amount > 0)
                <div class="flex justify-between gap-2 text-[#1F7A4C]">
                    <span>Potongan Promo / Voucher</span>
                    <span class="font-bold tabular-nums whitespace-nowrap">-Rp{{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                </div>
            @endif
            @if($order->tax_rate > 0)
                <div class="flex justify-between gap-2 text-[#6F6259]">
                    <span>Pajak ({{ $taxRateFormatted }}%)</span>
                    <span class="font-bold text-[#1F1A17] tabular-nums whitespace-nowrap">Rp{{ number_format($order->tax_amount, 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="pt-2 border-t border-[#E6DED2] flex justify-between gap-2 text-base font-extrabold text-[#1F1A17]">
                <span>Total</span>
                <span class="tabular-nums whitespace-nowrap">{{ $order->formattedTotal() }}</span>
            </div>
        </div>

        <div class="pt-3 border-t border-[#F2ECE1] flex flex-wrap gap-2">
            @if($order->status->value === 'Menunggu Pembayaran')
                <a href="{{ route('order.payment', ['public_token' => $order->public_token]) }}"
                   class="min-h-[42px] inline-flex items-center px-4 py-2 rounded-xl bg-[#A9552F] text-white text-xs font-extrabold hover:bg-[#8E4423] transition">
                    Selesaikan Pembayaran →
                </a>
            @elseif(!in_array($order->status->value, ['Selesai', 'Dibatalkan'], true))
                <a href="{{ route('order.track', ['public_token' => $order->public_token]) }}"
                   class="min-h-[42px] inline-flex items-center px-4 py-2 rounded-xl bg-[#A9552F] text-white text-xs font-extrabold hover:bg-[#8E4423] transition">
                    Lacak Status Pesanan →
                </a>
            @endif

            @if($order->invoice)
                <a href="{{ route('order.invoice.show', ['public_token' => $order->public_token]) }}"
                   class="min-h-[42px] inline-flex items-center px-4 py-2 rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-bold hover:bg-[#2A1E17] transition">
                    Lihat Invoice Digital ({{ $order->invoice->invoice_number }})
                </a>
                <a href="{{ route('order.invoice.pdf', ['public_token' => $order->public_token]) }}"
                   class="min-h-[42px] inline-flex items-center px-4 py-2 rounded-xl border border-[#3B2A20] text-[#3B2A20] text-xs font-bold hover:bg-[#F2ECE1] transition">
                    Unduh PDF
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
