@extends('layouts.customer', ['title' => 'Invoice Digital ' . $invoice->invoice_number])

@section('content')
@php
    $displayEmail = $snapshot['customer_email'] ?? $order->customer_email;
    $taxRateFormatted = rtrim(rtrim(number_format((float) ($order->tax_rate ?? 0), 2, '.', ''), '0'), '.');
@endphp
<div class="max-w-3xl mx-auto space-y-4">
    <div class="print:hidden flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('order.track', ['public_token' => $order->public_token]) }}" class="min-h-[42px] inline-flex items-center text-xs font-bold text-[#6F6259] hover:text-[#3B2A20]">
            ← Kembali ke Pelacakan Pesanan
        </a>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="window.print()" class="min-h-[42px] px-4 py-2 rounded-xl border border-[#3B2A20] text-[#3B2A20] text-xs font-bold hover:bg-[#F2ECE1] transition">
                Cetak
            </button>
            <a href="{{ route('order.invoice.pdf', ['public_token' => $order->public_token]) }}" class="min-h-[42px] inline-flex items-center px-4 py-2 rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-bold hover:bg-[#2A1E17] transition">
                Unduh PDF
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-6 shadow-sm space-y-5 print:border-0 print:shadow-none">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-[#E6DED2] pb-4">
            <div class="flex items-start gap-3 min-w-0">
                <img src="{{ asset('images/logo-774.png') }}" alt="774 HUB" class="w-11 h-11 rounded-full object-cover shrink-0 ring-1 ring-black/10">
                <div class="min-w-0">
                    <div class="text-xl font-extrabold text-[#3B2A20]">{{ $snapshot['outlet_name'] ?? '774 HUB' }}</div>
                    <div class="text-xs text-[#6F6259] mt-0.5">{{ $snapshot['outlet_address'] ?? 'Jl. Bung Tarjo No. 12, Yogyakarta' }}</div>
                    <div class="text-xs text-[#6F6259]">Telp: {{ $snapshot['outlet_phone'] ?? '0274-555774' }}</div>
                </div>
            </div>
            <div class="text-left sm:text-right">
                <span class="inline-block px-2.5 py-0.5 rounded-full bg-[#DCFCE7] text-[#166534] text-xs font-extrabold uppercase">
                    LUNAS / DIBAYAR
                </span>
                <div class="text-sm font-extrabold font-mono text-[#1F1A17] mt-1 break-all">{{ $invoice->invoice_number }}</div>
                <div class="text-xs text-[#6F6259]">{{ $snapshot['issued_at'] ?? ($invoice->issued_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB') }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="min-w-0">
                <div class="text-[#6F6259] font-semibold uppercase">Pelanggan</div>
                <div class="font-bold text-[#1F1A17] mt-0.5 break-words">{{ $snapshot['customer_name'] ?? $order->customer_name }}</div>
                @if(!empty($displayEmail))
                    <div class="text-[#6F6259] break-all">{{ $displayEmail }}</div>
                @endif
                @if(!empty($snapshot['member_code']))
                    <div class="text-[#A9552F] font-bold">Member: {{ $snapshot['member_code'] }}</div>
                @endif
            </div>
            <div class="text-left sm:text-right min-w-0">
                <div class="text-[#6F6259] font-semibold uppercase">Detail Transaksi</div>
                <div class="font-mono font-bold text-[#1F1A17] mt-0.5 break-all">{{ $order->order_number }}</div>
                <div class="text-[#6F6259]">{{ $snapshot['table_label'] ?? ($order->table?->label ?? 'Takeaway') }}</div>
                <div class="text-[#3B2A20] font-bold">Metode: {{ $snapshot['payment_method'] ?? strtoupper($order->payment_method?->value ?? 'Tunai') }}</div>
            </div>
        </div>

        <div class="border-t border-b border-[#E6DED2] py-3 overflow-x-auto">
            <table class="w-full min-w-[420px] text-xs">
                <thead>
                    <tr class="text-left text-[#6F6259] border-b border-[#F2ECE1]">
                        <th class="pb-2 pr-3">Menu</th>
                        <th class="pb-2 px-2 text-center whitespace-nowrap">Jml</th>
                        <th class="pb-2 px-2 text-right whitespace-nowrap">Harga</th>
                        <th class="pb-2 pl-2 text-right whitespace-nowrap">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#FAF6EF]">
                    @foreach($order->items as $item)
                        <tr>
                            <td class="py-2 pr-3 font-bold text-[#1F1A17]">
                                {{ $item->product_name }}
                                @if($item->note)
                                    <div class="font-normal text-[#A9552F]">Catatan: {{ $item->note }}</div>
                                @endif
                            </td>
                            <td class="py-2 px-2 text-center tabular-nums whitespace-nowrap">{{ $item->quantity }}</td>
                            <td class="py-2 px-2 text-right tabular-nums whitespace-nowrap">Rp{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="py-2 pl-2 text-right font-bold tabular-nums whitespace-nowrap">Rp{{ number_format($item->line_total, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="space-y-1.5 text-xs">
            <div class="flex justify-between gap-2 text-[#6F6259]">
                <span>Subtotal</span>
                <span class="font-bold text-[#1F1A17] tabular-nums whitespace-nowrap">Rp{{ number_format($order->subtotal, 0, ',', '.') }}</span>
            </div>
            @if($order->discount_amount > 0)
                <div class="flex justify-between gap-2 text-[#1F7A4C]">
                    <span>Potongan Promo / Reward</span>
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
                <span>Total Dibayar</span>
                <span class="tabular-nums text-[#3B2A20] whitespace-nowrap">{{ $order->formattedTotal() }}</span>
            </div>
        </div>

        <div class="pt-3 border-t border-[#F2ECE1] text-center text-xs text-[#6F6259]">
            Terima kasih telah berkunjung ke 774 HUB. Kode Penyerahan Anda: <strong class="font-mono text-[#3B2A20]">{{ $order->order_code }}</strong>
        </div>
    </div>
</div>
@endsection
