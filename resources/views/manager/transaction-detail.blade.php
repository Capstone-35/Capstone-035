@extends('layouts.staff', ['title' => 'Detail Transaksi ' . $order->order_number])

@section('heading', 'Manager — Rincian Transaksi ' . $order->order_number)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-[#6F6259]">Audit & Manajemen Transaksi</span>
                <h2 class="text-xl font-extrabold font-mono text-[#1F1A17]">{{ $order->order_number }}</h2>
                <div class="text-xs text-[#6F6259]">
                    Waktu Order: {{ $order->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }} WIB
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-status-badge :status="$order->status" />
                <span class="px-3 py-1.5 rounded-xl bg-[#3B2A20] text-white font-mono font-extrabold text-sm tracking-widest">
                    KODE: {{ $order->order_code }}
                </span>
            </div>
        </div>

        <!-- Manager Cancellation Authority (D10) -->
        @if(in_array($order->status->value, ['Menunggu Pembayaran', 'Dibayar', 'Diproses'], true))
            <div class="p-4 rounded-xl bg-red-50 border border-red-200">
                <h3 class="text-xs font-bold uppercase text-red-900 mb-1">Otoritas Pembatalan Transaksi oleh Manager (D10)</h3>
                @if(in_array($order->status->value, ['Dibayar', 'Diproses'], true))
                    <div class="mb-3 p-3 rounded-lg bg-amber-50 border border-amber-300 text-xs font-semibold text-amber-900">
                        <strong>Perhatian:</strong> Pesanan ini sudah berstatus <strong>{{ $order->status->value }}</strong>. Pembatalan memerlukan alasan wajib dan akan membatalkan poin loyalti serta mengembalikan voucher promo.
                        <div class="mt-1 font-bold">Catatan: Pengembalian dana ditangani di luar sistem (D10).</div>
                    </div>
                @endif

                <form method="POST" action="{{ route('manager.orders.cancel', ['order' => $order->order_number]) }}"
                      onsubmit="return confirm('Apakah Anda yakin ingin membatalkan transaksi ini? Tindakan tidak dapat dibatalkan.');"
                      class="flex flex-wrap items-center gap-2">
                    @csrf
                    <input type="text" name="reason" placeholder="Alasan pembatalan oleh manager..." required
                           class="flex-1 min-w-[240px] px-3.5 py-2 rounded-xl border border-red-300 bg-white text-xs">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-red-700 hover:bg-red-800 text-white font-extrabold text-xs">
                        Batalkan Transaksi Ini
                    </button>
                </form>
            </div>
        @endif
    </div>

    <!-- Items and Payment Attempts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Items Table -->
            <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-3">
                <h3 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Rincian Menu Transaksi</h3>
                <div class="divide-y divide-[#F2ECE1] text-xs">
                    @foreach($order->items as $it)
                        <div class="py-2.5 flex justify-between items-start">
                            <div>
                                <span class="font-bold text-[#1F1A17]">{{ $it->quantity }}x {{ $it->product_name }}</span>
                                <div class="text-[#6F6259]">Rp{{ number_format($it->unit_price, 0, ',', '.') }} / porsi</div>
                                @if($it->note)
                                    <div class="text-[#A9552F]">Catatan: "{{ $it->note }}"</div>
                                @endif
                            </div>
                            <span class="font-extrabold tabular-nums">Rp{{ number_format($it->line_total, 0, ',', '.') }}</span>
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
                            <span>Diskon</span>
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
                        <span>Total Transaksi</span>
                        <span class="tabular-nums text-[#3B2A20]">{{ $order->formattedTotal() }}</span>
                    </div>
                </div>
            </div>

            <!-- Payment Attempts Log (1 Order : N Payments - D9) -->
            <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-3">
                <h3 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Riwayat Upaya Pembayaran (D9)</h3>
                @if($order->payments->isEmpty())
                    <p class="text-xs text-[#6F6259]">Belum ada data pembayaran tercatat.</p>
                @else
                    <div class="space-y-2 text-xs">
                        @foreach($order->payments as $p)
                            <div class="p-3 rounded-xl border border-[#E6DED2] bg-[#FAF6EF] flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <div class="font-bold text-[#1F1A17] uppercase">{{ $p->method->value }} • {{ $p->provider }}</div>
                                    @if($p->provider_reference)
                                        <div class="font-mono text-[11px] text-[#6F6259]">Ref: {{ $p->provider_reference }}</div>
                                    @endif
                                    <div class="text-[11px] text-[#6F6259]">{{ $p->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}</div>
                                </div>
                                <div class="text-right">
                                    <x-status-badge :status="$p->status->value" />
                                    <div class="font-extrabold text-sm tabular-nums mt-1">Rp{{ number_format($p->amount, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar Meta -->
        <div class="space-y-4 text-xs">
            <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-2.5">
                <h3 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Informasi Pembeli</h3>
                <div>
                    <span class="text-[#6F6259]">Nama:</span>
                    <div class="font-bold text-[#1F1A17] mt-0.5">{{ $order->customer_name }}</div>
                </div>
                <div>
                    <span class="text-[#6F6259]">Email:</span>
                    <div class="font-bold text-[#1F1A17] mt-0.5">{{ $order->customer_email ?: '—' }}</div>
                </div>
                <div>
                    <span class="text-[#6F6259]">Telepon:</span>
                    <div class="font-bold text-[#1F1A17] mt-0.5">{{ $order->customer_phone ?: '—' }}</div>
                </div>
                <div>
                    <span class="text-[#6F6259]">Meja:</span>
                    <div class="font-bold text-[#1F1A17] mt-0.5">{{ $order->table?->label ?? 'Takeaway' }}</div>
                </div>
                <div>
                    <span class="text-[#6F6259]">Member ID:</span>
                    <div class="font-bold text-[#A9552F] mt-0.5">{{ $order->member ? $order->member->member_code : 'Tamu (Guest)' }}</div>
                </div>
            </div>

            <!-- Status History -->
            <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-2">
                <h3 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Audit Status</h3>
                <div class="space-y-2">
                    @foreach($order->statusHistories as $h)
                        <div class="border-b border-[#FAF6EF] pb-1.5 last:border-0">
                            <div class="flex justify-between font-bold">
                                <span>{{ $h->to_status }}</span>
                                <span class="font-mono text-[#6F6259]">{{ $h->created_at?->timezone('Asia/Jakarta')->format('H:i:s') }}</span>
                            </div>
                            <div class="text-[11px] text-[#6F6259]">Sumber: {{ $h->source }} • {{ $h->reason }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
