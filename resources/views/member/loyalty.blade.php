@extends('layouts.customer', ['title' => 'Loyalti & Voucher Hadiah'])

@section('content')
<div class="space-y-4">
    <!-- Breadcrumb / Header Navigation -->
    <div class="flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('member.dashboard') }}" class="text-xs font-bold text-[#6F6259] hover:text-[#3B2A20] inline-flex items-center gap-1">
            <span>←</span> Kembali ke Portal Member
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('member.profile') }}" class="text-xs font-bold text-[#A9552F] hover:underline">
                Profil & Keamanan Akun
            </a>
            <span class="text-gray-300">•</span>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-xs font-bold text-red-600 hover:underline">
                    Keluar
                </button>
            </form>
        </div>
    </div>

    <!-- Points Balance Card -->
    <div class="bg-[#3B2A20] text-[#FAF6EF] rounded-2xl p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <span class="text-xs uppercase tracking-wider font-bold text-[#FAF6EF]/75">Saldo Poin Member</span>
                <div class="text-3xl font-extrabold mt-1 tabular-nums">
                    {{ number_format($member->points_balance, 0, ',', '.') }} <span class="text-lg font-bold">Poin</span>
                </div>
                <div class="text-xs text-[#FAF6EF]/70 mt-0.5">Kode Member: <strong class="text-white font-mono">{{ $member->member_code }}</strong></div>
            </div>
            <a href="{{ route('menu.index') }}" class="px-4 py-2.5 rounded-xl bg-[#A9552F] text-white text-xs font-bold hover:bg-[#8E4423] shadow-sm">
                + Kumpulkan Poin (Pesan Menu)
            </a>
        </div>
        <p class="mt-4 text-xs text-[#FAF6EF]/85 border-t border-white/10 pt-3">
            Dapatkan <strong>1 poin setiap kelipatan Rp{{ number_format($earnUnitAmount, 0, ',', '.') }}</strong> subtotal transaksi setelah diskon (diberikan saat pesanan berstatus <strong>{{ $earnOnStatus }}</strong>).
        </p>
    </div>

    <!-- Active Vouchers -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-extrabold text-[#1F1A17]">Voucher Reward Saya</h2>
            <span class="px-2.5 py-0.5 rounded-full bg-[#FAF6EF] text-[#3B2A20] border border-[#E6DED2] text-xs font-bold">
                {{ $redemptions->filter->isUsable()->count() }} Aktif
            </span>
        </div>
        @php
            $activeList = $redemptions->filter->isUsable();
        @endphp

        @if($activeList->isEmpty())
            <p class="text-xs text-[#6F6259]">Anda belum memiliki voucher reward aktif. Tukar poin Anda pada katalog di bawah untuk mendapatkan potongan harga!</p>
        @else
            <div class="space-y-2.5">
                @foreach($activeList as $redemption)
                    <div class="p-3.5 rounded-xl border border-[#86EFAC] bg-[#DCFCE7]/40 flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div>
                            <div class="font-extrabold text-sm text-[#166534]">{{ $redemption->reward?->name }}</div>
                            <div class="text-[#1F1A17] mt-0.5 font-mono font-bold">Kode: {{ $redemption->voucher_code }}</div>
                            @if($redemption->expires_at)
                                <div class="text-[#6F6259] text-[11px]">Berlaku hingga: {{ $redemption->expires_at->timezone('Asia/Jakarta')->format('d M Y') }}</div>
                            @endif
                        </div>
                        <a href="{{ route('checkout.index', ['reward_redemption_id' => $redemption->id]) }}"
                           class="px-3.5 py-2 rounded-xl bg-[#166534] text-white text-xs font-bold hover:bg-emerald-900 shadow-sm">
                            Gunakan di Checkout
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Rewards Catalogue -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-extrabold text-[#1F1A17]">Katalog Penukaran Reward</h2>
            <span class="text-xs text-[#6F6259] font-medium">Tukar poin dengan diskon</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @forelse($rewards as $reward)
                @php
                    $canRedeem = $member->points_balance >= $reward->points_cost;
                @endphp
                <div class="p-4 rounded-xl border border-[#E6DED2] bg-[#FAF6EF] flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-bold text-sm text-[#1F1A17]">{{ $reward->name }}</h3>
                            <span class="px-2 py-0.5 rounded-full bg-[#3B2A20] text-[#FAF6EF] text-[11px] font-extrabold tabular-nums shrink-0">
                                {{ number_format($reward->points_cost, 0, ',', '.') }} Poin
                            </span>
                        </div>
                        @if($reward->description)
                            <p class="text-xs text-[#6F6259] mt-1">{{ $reward->description }}</p>
                        @endif
                        <div class="text-[11px] text-[#A9552F] font-semibold mt-1">
                            Masa aktif voucher: {{ $reward->valid_days }} hari
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-[#E6DED2]">
                        @if($canRedeem)
                            <form method="POST" action="{{ route('member.rewards.redeem', ['reward' => $reward->id]) }}"
                                  onsubmit="return confirm('Tukar {{ number_format($reward->points_cost, 0, ',', '.') }} poin untuk voucher {{ $reward->name }}?');">
                                @csrf
                                <button type="submit" class="w-full min-h-[40px] rounded-xl bg-[#1F7A4C] text-white text-xs font-bold hover:bg-emerald-800 transition shadow-sm">
                                    Tukar Reward ({{ number_format($reward->points_cost, 0, ',', '.') }} Poin)
                                </button>
                            </form>
                        @else
                            <button type="button" disabled class="w-full min-h-[40px] rounded-xl bg-gray-200 text-gray-500 text-xs font-bold cursor-not-allowed">
                                Butuh {{ number_format($reward->points_cost - $member->points_balance, 0, ',', '.') }} Poin Lagi
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-xs text-[#6F6259] col-span-2">Belum ada reward yang aktif saat ini.</p>
            @endforelse
        </div>
    </div>

    <!-- Point Ledger History -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-3">
        <h2 class="text-base font-extrabold text-[#1F1A17]">Riwayat Mutasi Poin</h2>
        @if($ledgers->isEmpty())
            <p class="text-xs text-[#6F6259]">Belum ada catatan mutasi poin loyalti.</p>
        @else
            <div class="divide-y divide-[#F2ECE1] text-xs">
                @foreach($ledgers as $ledger)
                    <div class="py-2.5 flex items-center justify-between gap-2">
                        <div>
                            <div class="font-bold text-[#1F1A17]">{{ $ledger->note ?: ($ledger->type === 'earn' ? 'Perolehan Poin' : ($ledger->type === 'redeem' ? 'Penukaran Reward' : 'Penyesuaian Poin')) }}</div>
                            <div class="text-[11px] text-[#6F6259]">
                                {{ $ledger->created_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }}
                                @if($ledger->order)
                                    • <a href="{{ route('member.orders.show', ['order' => $ledger->order->order_number]) }}" class="font-mono text-[#A9552F] hover:underline">{{ $ledger->order->order_number }}</a>
                                @endif
                                • Saldo Akhir: <strong class="text-[#3B2A20]">{{ number_format($ledger->balance_after, 0, ',', '.') }} Poin</strong>
                            </div>
                        </div>
                        <div class="font-extrabold text-sm tabular-nums shrink-0 {{ $ledger->points >= 0 ? 'text-[#1F7A4C]' : 'text-[#B91C1C]' }}">
                            {{ $ledger->points >= 0 ? '+' : '' }}{{ number_format($ledger->points, 0, ',', '.') }}
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="pt-2">
                {{ $ledgers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
