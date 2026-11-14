@extends('layouts.customer', ['title' => 'Promosi Behavioral Member'])

@section('content')
<div class="space-y-4">
    <div class="bg-[#3B2A20] text-[#FAF6EF] rounded-2xl p-5 shadow-sm">
        <span class="inline-block px-2.5 py-0.5 rounded-full bg-[#A9552F] text-white text-xs font-extrabold uppercase">
            Behavioral Promotion Engine (RFM)
        </span>
        <h1 class="mt-2 text-xl font-extrabold">Promosi Khusus Member 774 HUB</h1>
        <p class="mt-1 text-xs text-[#FAF6EF]/80">
            Sistem promosi 774 HUB memberikan penawaran otomatis berdasarkan kebiasaan kunjungan, frekuensi repeat order, dan jam sepi (Slow Hour).
        </p>

        @if(!$member)
            <!-- D13: Guest sees promos as information with CTA "Daftar untuk memakai promo" -->
            <div class="mt-4 pt-3 border-t border-white/15 flex flex-wrap items-center justify-between gap-2">
                <span class="text-xs font-semibold">Seluruh promosi dan voucher reward berlaku khusus untuk Member.</span>
                <a href="{{ route('register') }}" class="px-4 py-2 rounded-xl bg-[#A9552F] text-white text-xs font-extrabold hover:bg-[#8E4423]">
                    Daftar untuk memakai promo →
                </a>
            </div>
        @endif
    </div>

    @if($promotions->isEmpty())
        <x-empty-state
            title="Belum Ada Promosi Aktif Saat Ini"
            description="Pantau terus halaman ini untuk penawaran spesial Member berikutnya."
            :action-url="route('menu.index')"
            action-label="Lihat Menu"
        />
    @else
        <div class="space-y-3">
            @foreach($promotions as $promo)
                @php
                    $isEligible = in_array($promo->id, $eligibleIds, true);
                @endphp
                <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="px-2.5 py-0.5 rounded-full bg-[#F2ECE1] text-[#3B2A20] text-[11px] font-bold">
                            {{ $promo->rule_type->label() }}
                        </span>
                        <span class="font-mono text-xs font-extrabold text-[#A9552F]">
                            Kode: {{ $promo->code }}
                        </span>
                    </div>

                    <h2 class="mt-2 text-base font-extrabold text-[#1F1A17]">{{ $promo->name }}</h2>
                    <p class="mt-1 text-xs text-[#6F6259]">{{ $promo->description }}</p>

                    <div class="mt-3 pt-3 border-t border-[#F2ECE1] flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div class="text-[#6F6259]">
                            Min. Transaksi: <strong class="text-[#1F1A17]">Rp{{ number_format($promo->min_transaction, 0, ',', '.') }}</strong>
                        </div>

                        @if($member)
                            @if($isEligible)
                                <a href="{{ route('checkout.index', ['promotion_id' => $promo->id]) }}"
                                   class="px-3.5 py-2 rounded-xl bg-[#1F7A4C] text-white font-bold">
                                    Memenuhi Syarat • Pakai di Checkout
                                </a>
                            @else
                                <span class="px-3 py-1.5 rounded-xl bg-[#FAF6EF] border border-[#E6DED2] text-[#6F6259] font-semibold">
                                    Belum Memenuhi Kriteria Saat Ini
                                </span>
                            @endif
                        @else
                            <a href="{{ route('register') }}" class="px-3.5 py-2 rounded-xl bg-[#3B2A20] text-[#FAF6EF] font-bold">
                                Daftar untuk memakai promo
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
