@extends('layouts.staff', ['title' => 'POS Dashboard'])

@section('heading', 'Kasir Point of Sale (POS) — Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Top Operational KPI Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-kpi-card
            label="Menunggu Pembayaran"
            :value="$waitingPaymentCount"
            subtext="Pesanan belum diverifikasi"
            badge="Antrean Kasir"
            :accent="$waitingPaymentCount > 0"
        />
        <x-kpi-card
            label="Siap Diserahkan"
            :value="$readyForHandoverCount"
            subtext="Pesanan siap di meja barista"
            badge="Perlu Handover"
        />
        <x-kpi-card
            label="Sedang Diproses KDS"
            :value="$activeKdsCount"
            subtext="Dapur / Barista aktif"
        />
        <x-kpi-card
            label="Pendapatan Hari Ini"
            :value="'Rp' . number_format($todayRevenue, 0, ',', '.')"
            :subtext="$todayOrdersCount . ' Transaksi Selesai/Dibayar'"
        />
    </div>

    <!-- Quick Action Bar -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('pos.orders.create') }}" class="px-5 py-3 rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-sm font-extrabold hover:bg-[#2A1E17] transition shadow-sm">
            + Buat Pesanan Manual (POS)
        </a>
        <a href="{{ route('pos.payments.index') }}" class="px-4 py-3 rounded-xl bg-white border border-[#D6CBBC] text-[#3B2A20] text-sm font-bold hover:bg-[#F2ECE1] transition">
            Verifikasi Pembayaran Tunai & QRIS ({{ $waitingPaymentCount }})
        </a>
        <a href="{{ route('pos.orders.index') }}" class="px-4 py-3 rounded-xl bg-white border border-[#D6CBBC] text-[#3B2A20] text-sm font-bold hover:bg-[#F2ECE1] transition">
            Papan Pesanan Masuk
        </a>
        <a href="{{ route('pos.invoices.index') }}" class="px-4 py-3 rounded-xl bg-white border border-[#D6CBBC] text-[#3B2A20] text-sm font-bold hover:bg-[#F2ECE1] transition">
            Monitoring Pengiriman Invoice
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Waiting Cash Confirmation Queue -->
        <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-[#F2ECE1] pb-3">
                <h2 class="text-base font-extrabold text-[#1F1A17]">Antrean Menunggu Pembayaran</h2>
                <a href="{{ route('pos.payments.index') }}" class="text-xs font-bold text-[#A9552F] hover:underline">Semua Pembayaran →</a>
            </div>

            @if($pendingCashOrders->isEmpty())
                <p class="text-xs text-[#6F6259] py-4 text-center">Tidak ada pesanan yang menunggu pembayaran saat ini.</p>
            @else
                <div class="space-y-3">
                    @foreach($pendingCashOrders as $order)
                        <div class="p-3.5 rounded-xl border border-[#FCD34D] bg-[#FEF3C7]/40 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-[#1F1A17] text-sm">{{ $order->order_number }}</span>
                                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-900 font-extrabold uppercase text-[10px]">
                                        {{ $order->payment_method?->value ?? 'Belum Pilih' }}
                                    </span>
                                </div>
                                <div class="text-[#6F6259] mt-0.5">
                                    {{ $order->table?->label ?? 'Takeaway' }} • <strong>{{ $order->customer_name }}</strong>
                                </div>
                                <div class="text-[#3B2A20] font-extrabold text-sm mt-1 tabular-nums">
                                    {{ $order->formattedTotal() }}
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('pos.orders.confirm_cash', ['order' => $order->order_number]) }}">
                                    @csrf
                                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-[#1F7A4C] text-white font-extrabold hover:bg-emerald-800 transition">
                                        Konfirmasi Tunai (Lunas)
                                    </button>
                                </form>
                                <a href="{{ route('pos.orders.show', ['order' => $order->order_number]) }}" class="px-3 py-2 rounded-xl border border-[#D6CBBC] bg-white font-bold text-[#3B2A20]">
                                    Rincian
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Ready for Handover Queue -->
        <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-[#F2ECE1] pb-3">
                <h2 class="text-base font-extrabold text-[#1F1A17]">Pesanan Siap Diserahkan (Handover)</h2>
                <span class="px-2.5 py-0.5 rounded-full bg-[#CCFBF1] text-[#115E59] text-xs font-bold">{{ $readyOrders->count() }} Siap</span>
            </div>

            @if($readyOrders->isEmpty())
                <p class="text-xs text-[#6F6259] py-4 text-center">Belum ada pesanan berstatus Siap yang menunggu penyerahan.</p>
            @else
                <div class="space-y-3">
                    @foreach($readyOrders as $order)
                        <div class="p-3.5 rounded-xl border border-[#5EEAD4] bg-[#CCFBF1]/30 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-[#1F1A17] text-sm">{{ $order->order_number }}</span>
                                    <span class="px-2.5 py-0.5 rounded-lg bg-[#3B2A20] text-white font-mono font-extrabold text-xs tracking-wider">
                                        KODE: {{ $order->order_code }}
                                    </span>
                                </div>
                                <div class="text-[#6F6259] mt-0.5">
                                    {{ $order->table?->label ?? 'Takeaway' }} • Pemesan: <strong>{{ $order->customer_name }}</strong>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('pos.orders.handover', ['order' => $order->order_number]) }}" class="flex items-center gap-2">
                                @csrf
                                <input type="text" name="order_code" placeholder="4 Karakter" maxlength="8" required
                                       class="w-24 px-2 py-1.5 rounded-lg border border-[#D6CBBC] bg-white font-mono font-bold text-center uppercase text-xs">
                                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-[#3B2A20] text-white font-bold hover:bg-[#2A1E17]">
                                    Serahkan
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
