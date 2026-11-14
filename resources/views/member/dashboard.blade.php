@extends('layouts.customer', ['title' => 'Portal Member'])

@section('content')
<div class="space-y-4">
    <!-- Member Header Card -->
    <div class="bg-[#3B2A20] text-[#FAF6EF] rounded-2xl p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <div class="text-xs uppercase tracking-wider font-bold text-[#FAF6EF]/75">Kode Member: {{ $member->member_code }}</div>
                <h1 class="text-xl font-extrabold mt-0.5">{{ $member->name }}</h1>
                <div class="text-xs text-[#FAF6EF]/80">{{ $member->email }} • {{ $member->phone }}</div>
            </div>
            <span class="px-3 py-1 rounded-full bg-[#A9552F] text-white text-xs font-extrabold">
                Segmen RFM: {{ $member->latestRfmResult?->segment_name ?? 'Member Baru' }}
            </span>
        </div>

        <div class="mt-4 pt-4 border-t border-white/15 grid grid-cols-2 gap-4">
            <div>
                <div class="text-xs text-[#FAF6EF]/75 font-semibold">Saldo Poin Loyalti</div>
                <div class="text-2xl font-extrabold tabular-nums mt-0.5">{{ number_format($member->points_balance, 0, ',', '.') }} Poin</div>
            </div>
            <div>
                <div class="text-xs text-[#FAF6EF]/75 font-semibold">Progres Repeat Order ({{ $repeatWindowDays }} Hari)</div>
                <div class="text-lg font-extrabold tabular-nums mt-0.5">{{ $recentSuccessCount }} / {{ $repeatThreshold }} Transaksi</div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ route('member.loyalty') }}" class="px-3.5 py-2 rounded-xl bg-white text-[#3B2A20] text-xs font-extrabold">
                Tukar Poin & Voucher ({{ $activeVouchers->count() }})
            </a>
            <a href="{{ route('member.orders.index') }}" class="px-3.5 py-2 rounded-xl bg-white/15 text-white text-xs font-bold hover:bg-white/25">
                Riwayat Transaksi
            </a>
            <a href="{{ route('member.profile') }}" class="px-3.5 py-2 rounded-xl bg-white/15 text-white text-xs font-bold hover:bg-white/25">
                Profil & Privasi
            </a>
            <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                @csrf
                <button type="submit" class="px-3 py-2 rounded-xl bg-red-900/60 text-red-100 text-xs font-bold hover:bg-red-800">
                    Keluar
                </button>
            </form>
        </div>
    </div>

    <!-- Active Eligible Promos -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-extrabold text-[#1F1A17]">Promosi yang Tersedia untuk Anda ({{ $activePromos->count() }})</h2>
            <a href="{{ route('promotions.public') }}" class="text-xs font-bold text-[#A9552F] hover:underline">Lihat Semua</a>
        </div>
        @if($activePromos->isEmpty())
            <p class="text-xs text-[#6F6259]">Belum ada promosi yang aktif pada jam ini. Terus bertransaksi untuk membuka Repeat Order Reward!</p>
        @else
            <div class="space-y-2">
                @foreach($activePromos as $promo)
                    <div class="p-3 rounded-xl bg-[#FAF6EF] border border-[#E6DED2] flex items-center justify-between gap-2 text-xs">
                        <div>
                            <div class="font-bold text-[#1F1A17]">{{ $promo->name }} ({{ $promo->code }})</div>
                            <div class="text-[#6F6259]">{{ $promo->description }}</div>
                        </div>
                        <a href="{{ route('menu.index') }}" class="shrink-0 px-3 py-1.5 rounded-lg bg-[#3B2A20] text-[#FAF6EF] font-bold">
                            Pesan
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Recent Orders with Repeat Order ("Pesan Lagi") -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-extrabold text-[#1F1A17]">Transaksi Terakhir</h2>
            <a href="{{ route('member.orders.index') }}" class="text-xs font-bold text-[#A9552F] hover:underline">Semua Riwayat →</a>
        </div>

        @if($recentOrders->isEmpty())
            <p class="text-xs text-[#6F6259]">Anda belum memiliki riwayat pesanan.</p>
        @else
            <div class="space-y-3">
                @foreach($recentOrders as $order)
                    <div class="p-3.5 rounded-xl bg-[#FAF6EF] border border-[#E6DED2] flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('member.orders.show', ['order' => $order->order_number]) }}" class="font-mono text-xs font-extrabold text-[#1F1A17] hover:underline">
                                    {{ $order->order_number }}
                                </a>
                                <x-status-badge :status="$order->status" />
                            </div>
                            <div class="text-xs text-[#6F6259] mt-1">
                                {{ $order->created_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }} • <strong>{{ $order->formattedTotal() }}</strong>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('member.orders.repeat', ['order' => $order->order_number]) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-xl bg-[#A9552F] text-white text-xs font-bold hover:bg-[#8E4423]">
                                    Pesan Lagi
                                </button>
                            </form>
                            <a href="{{ route('member.orders.show', ['order' => $order->order_number]) }}"
                               class="px-3 py-1.5 rounded-xl border border-[#D6CBBC] bg-white text-xs font-bold text-[#3B2A20]">
                                Detail
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
