@extends('layouts.customer', ['title' => 'Portal Member'])

@section('content')
<div class="max-w-5xl mx-auto space-y-5">
    <!-- Member Header Card -->
    <div class="bg-[#3B2A20] text-[#FAF6EF] rounded-2xl p-4 sm:p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-2.5">
            <div class="min-w-0">
                <div class="text-xs uppercase tracking-wider font-bold text-[#FAF6EF]/75">Kode Member: {{ $member->member_code }}</div>
                <h1 class="text-lg sm:text-xl font-extrabold mt-0.5 break-words">{{ $member->name }}</h1>
                <div class="text-xs text-[#FAF6EF]/80 break-all">{{ $member->email }} • {{ $member->phone }}</div>
            </div>
            <span class="px-3 py-1 rounded-full bg-[#A9552F] text-white text-xs font-extrabold whitespace-nowrap shrink-0">
                Segmen RFM: {{ $member->latestRfmResult?->segment_name ?? 'Member Baru' }}
            </span>
        </div>

        <div class="mt-4 pt-4 border-t border-white/15 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <div class="text-xs text-[#FAF6EF]/75 font-semibold">Saldo Poin Loyalti</div>
                <div class="text-2xl font-extrabold tabular-nums mt-0.5">{{ number_format($member->points_balance, 0, ',', '.') }} Poin</div>
            </div>
            <div>
                <div class="text-xs text-[#FAF6EF]/75 font-semibold">Progres Repeat Order ({{ $repeatWindowDays }} Hari)</div>
                <div class="text-lg sm:text-xl font-extrabold tabular-nums mt-0.5">{{ $recentSuccessCount }} / {{ $repeatThreshold }} Transaksi</div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <a href="{{ route('member.loyalty') }}" class="min-h-[42px] inline-flex items-center px-3.5 py-2 rounded-xl bg-white text-[#3B2A20] text-xs font-extrabold hover:bg-[#FAF6EF] transition">
                Tukar Poin & Voucher ({{ $activeVouchers->count() }})
            </a>
            <a href="{{ route('member.orders.index') }}" class="min-h-[42px] inline-flex items-center px-3.5 py-2 rounded-xl bg-white/15 text-white text-xs font-bold hover:bg-white/25 transition">
                Riwayat Transaksi
            </a>
            <a href="{{ route('member.profile') }}" class="min-h-[42px] inline-flex items-center px-3.5 py-2 rounded-xl bg-white/15 text-white text-xs font-bold hover:bg-white/25 transition">
                Profil & Privasi
            </a>
            <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Apakah Anda yakin ingin keluar dari akun Member?');" class="sm:ml-auto">
                @csrf
                <button type="submit" class="min-h-[42px] px-3.5 py-2 rounded-xl bg-red-900/60 text-red-100 text-xs font-bold hover:bg-red-800 transition">
                    Keluar
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        <!-- Active Eligible Promos -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <h2 class="text-sm font-extrabold text-[#1F1A17]">Promosi Tersedia ({{ $activePromos->count() }})</h2>
                <a href="{{ route('promotions.public') }}" class="min-h-[36px] inline-flex items-center text-xs font-bold text-[#A9552F] hover:underline whitespace-nowrap shrink-0">Lihat Semua</a>
            </div>
            @if($activePromos->isEmpty())
                <p class="text-xs text-[#6F6259]">Belum ada promosi yang aktif pada jam ini. Terus bertransaksi untuk membuka Repeat Order Reward!</p>
            @else
                <div class="space-y-2.5">
                    @foreach($activePromos as $promo)
                        <div class="p-3 rounded-xl bg-[#FAF6EF] border border-[#E6DED2] flex flex-wrap sm:flex-nowrap items-center justify-between gap-2 text-xs">
                            <div class="min-w-0">
                                <div class="font-bold text-[#1F1A17] break-words">{{ $promo->name }} ({{ $promo->code }})</div>
                                <div class="text-[#6F6259] mt-0.5">{{ $promo->description }}</div>
                            </div>
                            <a href="{{ route('menu.index') }}" class="shrink-0 min-h-[38px] inline-flex items-center px-3.5 py-1.5 rounded-lg bg-[#3B2A20] text-[#FAF6EF] font-bold hover:bg-[#2A1E17] transition">
                                Pesan
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Recent Orders with Repeat Order ("Pesan Lagi") -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <h2 class="text-sm font-extrabold text-[#1F1A17]">Transaksi Terakhir</h2>
                <a href="{{ route('member.orders.index') }}" class="min-h-[36px] inline-flex items-center text-xs font-bold text-[#A9552F] hover:underline whitespace-nowrap shrink-0">Semua Riwayat →</a>
            </div>

            @if($recentOrders->isEmpty())
                <p class="text-xs text-[#6F6259]">Anda belum memiliki riwayat pesanan.</p>
            @else
                <div class="space-y-3">
                    @foreach($recentOrders as $order)
                        <div class="p-3.5 rounded-xl bg-[#FAF6EF] border border-[#E6DED2] flex flex-wrap items-center justify-between gap-2.5">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('member.orders.show', ['order' => $order->order_number]) }}" class="font-mono text-xs font-extrabold text-[#1F1A17] hover:underline break-all">
                                        {{ $order->order_number }}
                                    </a>
                                    <x-status-badge :status="$order->status" />
                                </div>
                                <div class="text-xs text-[#6F6259] mt-1">
                                    {{ $order->created_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }} • <strong>{{ $order->formattedTotal() }}</strong>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                <form method="POST" action="{{ route('member.orders.repeat', ['order' => $order->order_number]) }}" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                                    @csrf
                                    <button type="submit" class="min-h-[40px] px-3.5 py-1.5 rounded-xl bg-[#A9552F] text-white text-xs font-bold hover:bg-[#8E4423] transition">
                                        Pesan Lagi
                                    </button>
                                </form>
                                <a href="{{ route('member.orders.show', ['order' => $order->order_number]) }}"
                                   class="min-h-[40px] inline-flex items-center px-3.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-white text-xs font-bold text-[#3B2A20] hover:bg-[#F2ECE1] transition">
                                    Detail
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
