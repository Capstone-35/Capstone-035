@extends('layouts.staff', ['title' => 'Detail Pesanan ' . $order->order_number])

@section('heading', 'POS — Detail Transaksi ' . $order->order_number)

@section('content')
<div class="space-y-6">
    <!-- Header Status Card -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-[#6F6259]">Nomor Transaksi</div>
                <h2 class="text-xl font-extrabold font-mono text-[#1F1A17]">{{ $order->order_number }}</h2>
                <div class="text-xs text-[#6F6259] mt-0.5">
                    Dibuat: {{ $order->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }} WIB • Sumber: <strong>{{ strtoupper($order->source) }}</strong>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <x-status-badge :status="$order->status" />
                <span class="px-3 py-1.5 rounded-xl bg-[#3B2A20] text-[#FAF6EF] font-mono font-extrabold text-sm tracking-widest">
                    KODE: {{ $order->order_code }}
                </span>
            </div>
        </div>

        <!-- Operational Action Bar based on Status -->
        <div class="mt-4 pt-4 border-t border-[#F2ECE1] flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                @if($order->status->value === 'Menunggu Pembayaran')
                    <!-- Cash confirmation form with custom cash received -->
                    <form method="POST" action="{{ route('pos.orders.confirm_cash', ['order' => $order->order_number]) }}" class="flex items-center gap-2">
                        @csrf
                        <input type="number" name="cash_received" min="{{ $order->total_amount }}" value="{{ $order->total_amount }}"
                               class="w-32 px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs font-bold tabular-nums"
                               placeholder="Uang Diterima">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-[#1F7A4C] text-white text-xs font-extrabold hover:bg-emerald-800 transition">
                            Konfirmasi Lunas (Tunai)
                        </button>
                    </form>

                    <form method="POST" action="{{ route('pos.orders.cancel', ['order' => $order->order_number]) }}"
                          onsubmit="return confirm('Batalkan pesanan ini?');">
                        @csrf
                        <input type="hidden" name="reason" value="Dibatalkan oleh Kasir di meja kasir">
                        <button type="submit" class="px-3 py-2 rounded-xl text-xs font-bold text-[#B91C1C] hover:bg-[#FEE2E2]">
                            Batalkan Pesanan
                        </button>
                    </form>
                @elseif($order->status->value === 'Siap')
                    <form method="POST" action="{{ route('pos.orders.handover', ['order' => $order->order_number]) }}" class="flex items-center gap-2">
                        @csrf
                        <input type="text" name="order_code" placeholder="Kode 4 Karakter" maxlength="8" required
                               class="w-32 px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs font-mono font-bold uppercase text-center">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-extrabold hover:bg-[#2A1E17]">
                            Verifikasi & Serahkan Pesanan (Selesai)
                        </button>
                    </form>
                @endif
            </div>

            @if($order->invoice)
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('pos.invoices.resend', ['invoice' => $order->invoice->id]) }}" class="flex items-center gap-2">
                        @csrf
                        <input type="hidden" name="channel" value="email">
                        <button type="submit" class="px-3.5 py-2 rounded-xl bg-[#F2ECE1] border border-[#D6CBBC] text-xs font-bold text-[#3B2A20] hover:bg-[#E6DED2]">
                            Kirim Ulang Invoice Email
                        </button>
                    </form>
                    <a href="{{ route('order.invoice.pdf', ['public_token' => $order->public_token]) }}" class="px-3.5 py-2 rounded-xl border border-[#D6CBBC] text-xs font-bold text-[#3B2A20] hover:bg-[#FAF6EF]">
                        Unduh PDF
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Items and Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Order Items -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-[#F2ECE1] pb-3">
                <h3 class="text-sm font-extrabold text-[#1F1A17]">Rincian Menu</h3>
                @if($order->isEditable())
                    <span class="text-xs font-bold text-[#A9552F]">Dapat Diubah Sebelum Dibayar (D8)</span>
                @else
                    <span class="text-xs font-semibold text-[#6F6259]">Terkunci (Sudah Dibayar)</span>
                @endif
            </div>

            <div class="divide-y divide-[#F2ECE1] text-xs">
                @foreach($order->items as $item)
                    <div class="py-3 flex items-start justify-between gap-3">
                        <div>
                            <div class="font-bold text-sm text-[#1F1A17]">{{ $item->quantity }}x {{ $item->product_name }}</div>
                            <div class="text-[#6F6259]">Rp{{ number_format($item->unit_price, 0, ',', '.') }} / porsi</div>
                            @if($item->note)
                                <div class="text-[#A9552F] mt-0.5">Catatan: "{{ $item->note }}"</div>
                            @endif
                        </div>
                        <div class="text-sm font-extrabold text-[#1F1A17] tabular-nums">
                            Rp{{ number_format($item->line_total, 0, ',', '.') }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="pt-3 border-t border-[#E6DED2] space-y-2 text-xs">
                <div class="flex justify-between text-[#6F6259]">
                    <span>Subtotal</span>
                    <span class="font-bold text-[#1F1A17] tabular-nums">Rp{{ number_format($order->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($order->discount_amount > 0)
                    <div class="flex justify-between text-[#1F7A4C]">
                        <span>Potongan Diskon ({{ $order->promotion?->name ?? 'Reward' }})</span>
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
                    <span>Total Pembayaran</span>
                    <span class="tabular-nums text-[#3B2A20]">{{ $order->formattedTotal() }}</span>
                </div>
            </div>
        </div>

        <!-- Customer & Payment Metadata -->
        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm text-xs space-y-3">
                <h3 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Informasi Pelanggan & Meja</h3>
                <div>
                    <span class="text-[#6F6259] font-semibold">Nama:</span>
                    <div class="font-bold text-sm text-[#1F1A17] mt-0.5">{{ $order->customer_name }}</div>
                </div>
                <div>
                    <span class="text-[#6F6259] font-semibold">Email:</span>
                    <div class="font-bold text-[#1F1A17] mt-0.5">{{ $order->customer_email ?: '—' }}</div>
                </div>
                <div>
                    <span class="text-[#6F6259] font-semibold">WhatsApp:</span>
                    <div class="font-bold text-[#1F1A17] mt-0.5">{{ $order->customer_phone ?: '—' }}</div>
                </div>
                <div>
                    <span class="text-[#6F6259] font-semibold">Meja:</span>
                    <div class="font-bold text-[#1F1A17] mt-0.5">{{ $order->table?->label ?? 'Takeaway / POS' }}</div>
                </div>
                <div>
                    <span class="text-[#6F6259] font-semibold">Status Member:</span>
                    <div class="mt-0.5">
                        @if($order->member)
                            <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-900 font-bold">
                                Member: {{ $order->member->member_code }}
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700 font-semibold">
                                Pelanggan Tamu (Guest)
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Audit Trail -->
            <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm text-xs space-y-3">
                <h3 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Jejak Status (Audit Log)</h3>
                <div class="space-y-2">
                    @foreach($order->statusHistories as $h)
                        <div class="border-b border-[#FAF6EF] pb-1.5 last:border-0">
                            <div class="flex justify-between font-bold">
                                <span>{{ $h->to_status }}</span>
                                <span class="font-mono text-[#6F6259]">{{ $h->created_at?->timezone('Asia/Jakarta')->format('H:i:s') }}</span>
                            </div>
                            @if($h->reason)
                                <div class="text-[#6F6259]">{{ $h->reason }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
