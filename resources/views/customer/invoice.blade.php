@extends('layouts.customer', ['title' => 'Invoice Digital ' . $invoice->invoice_number])

@section('content')
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('order.track', ['public_token' => $order->public_token]) }}" class="text-xs font-bold text-[#6F6259] hover:text-[#3B2A20]">
            ← Kembali ke Pelacakan Pesanan
        </a>
        <div class="flex gap-2">
            <button type="button" onclick="window.print()" class="px-3.5 py-2 rounded-xl border border-[#3B2A20] text-[#3B2A20] text-xs font-bold hover:bg-[#F2ECE1]">
                Cetak
            </button>
            <a href="{{ route('order.invoice.pdf', ['public_token' => $order->public_token]) }}" class="px-3.5 py-2 rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-bold">
                Unduh PDF
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-[#E6DED2] p-6 shadow-sm space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-[#E6DED2] pb-4">
            <div>
                <div class="text-xl font-extrabold text-[#3B2A20]">{{ $snapshot['outlet_name'] ?? '774 HUB' }}</div>
                <div class="text-xs text-[#6F6259] mt-0.5">{{ $snapshot['outlet_address'] ?? 'Jl. Bung Tarjo No. 12, Yogyakarta' }}</div>
                <div class="text-xs text-[#6F6259]">Telp: {{ $snapshot['outlet_phone'] ?? '0274-555774' }}</div>
            </div>
            <div class="text-right">
                <span class="inline-block px-2.5 py-0.5 rounded-full bg-[#DCFCE7] text-[#166534] text-xs font-extrabold uppercase">
                    LUNAS / DIBAYAR
                </span>
                <div class="text-sm font-extrabold font-mono text-[#1F1A17] mt-1">{{ $invoice->invoice_number }}</div>
                <div class="text-xs text-[#6F6259]">{{ $snapshot['issued_at'] ?? $invoice->issued_at?->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 text-xs">
            <div>
                <div class="text-[#6F6259] font-semibold uppercase">Pelanggan</div>
                <div class="font-bold text-[#1F1A17] mt-0.5">{{ $snapshot['customer_name'] ?? $order->customer_name }}</div>
                @if(!empty($snapshot['customer_email']))
                    <div class="text-[#6F6259]">{{ $snapshot['customer_email'] }}</div>
                @endif
                @if(!empty($snapshot['member_code']))
                    <div class="text-[#A9552F] font-bold">Member: {{ $snapshot['member_code'] }}</div>
                @endif
            </div>
            <div class="text-right">
                <div class="text-[#6F6259] font-semibold uppercase">Detail Transaksi</div>
                <div class="font-mono font-bold text-[#1F1A17] mt-0.5">{{ $order->order_number }}</div>
                <div class="text-[#6F6259]">{{ $snapshot['table_label'] ?? ($order->table?->label ?? 'Takeaway') }}</div>
                <div class="text-[#3B2A20] font-bold">Metode: {{ $snapshot['payment_method'] ?? strtoupper($order->payment_method?->value ?? 'Tunai') }}</div>
            </div>
        </div>

        <div class="border-t border-b border-[#E6DED2] py-3">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left text-[#6F6259] border-b border-[#F2ECE1]">
                        <th class="pb-2">Menu</th>
                        <th class="pb-2 text-center">Jml</th>
                        <th class="pb-2 text-right">Harga</th>
                        <th class="pb-2 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#FAF6EF]">
                    @foreach($order->items as $item)
                        <tr>
                            <td class="py-2 font-bold text-[#1F1A17]">
                                {{ $item->product_name }}
                                @if($item->note)
                                    <div class="font-normal text-[#A9552F]">Catatan: {{ $item->note }}</div>
                                @endif
                            </td>
                            <td class="py-2 text-center tabular-nums">{{ $item->quantity }}</td>
                            <td class="py-2 text-right tabular-nums">Rp{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="py-2 text-right font-bold tabular-nums">Rp{{ number_format($item->line_total, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="space-y-1.5 text-xs">
            <div class="flex justify-between text-[#6F6259]">
                <span>Subtotal</span>
                <span class="font-bold text-[#1F1A17] tabular-nums">Rp{{ number_format($order->subtotal, 0, ',', '.') }}</span>
            </div>
            @if($order->discount_amount > 0)
                <div class="flex justify-between text-[#1F7A4C]">
                    <span>Potongan Promo / Reward</span>
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
                <span>Total Dibayar</span>
                <span class="tabular-nums text-[#3B2A20]">{{ $order->formattedTotal() }}</span>
            </div>
        </div>

        <div class="pt-3 border-t border-[#F2ECE1] text-center text-xs text-[#6F6259]">
            Terima kasih telah berkunjung ke 774 HUB. Kode Penyerahan Anda: <strong class="font-mono text-[#3B2A20]">{{ $order->order_code }}</strong>
        </div>
    </div>
</div>
@endsection
