@extends('layouts.customer', ['title' => 'Checkout Pesanan'])

@section('content')
@php
    $taxRateFormatted = rtrim(rtrim(number_format((float) ($pricing['tax_rate'] ?? 0), 2, '.', ''), '0'), '.');
@endphp
<div class="max-w-5xl mx-auto space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-lg sm:text-xl font-extrabold text-[#1F1A17]">Checkout Pesanan</h1>
        <a href="{{ route('cart.index') }}" class="min-h-[40px] inline-flex items-center px-3 rounded-xl bg-[#F2ECE1] text-xs font-bold text-[#3B2A20] hover:bg-[#E6DED2] transition">
            ← Ubah Keranjang
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        <div class="lg:col-span-7 space-y-4">
            <!-- Step 1: Member Promo & Voucher Selector (D13, D14: At most one promo OR one reward per order) -->
            @if($member)
                <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <div class="min-w-0">
                            <h2 class="text-sm font-extrabold text-[#1F1A17]">Promo Behavioral & Voucher Reward Member</h2>
                            <p class="text-xs text-[#6F6259]">Maksimal 1 promo atau 1 voucher reward dapat dipilih per transaksi.</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full bg-[#F2ECE1] text-[#3B2A20] text-xs font-bold whitespace-nowrap shrink-0">
                            {{ number_format($member->points_balance, 0, ',', '.') }} Poin
                        </span>
                    </div>

                    <div class="space-y-2 text-xs">
                        <a href="{{ route('checkout.index') }}" onclick="saveCheckoutDraft()"
                           class="block p-3 rounded-xl border transition {{ !$selectedPromoId && !$selectedVoucherId ? 'border-[#3B2A20] bg-[#FAF6EF] font-bold' : 'border-[#E6DED2] hover:bg-[#FAF6EF]' }}">
                            Tanpa Promo / Voucher
                        </a>

                        @foreach($eligiblePromos as $promo)
                            <a href="{{ route('checkout.index', ['promotion_id' => $promo->id]) }}" onclick="saveCheckoutDraft()"
                               class="block p-3 rounded-xl border transition {{ (int)$selectedPromoId === (int)$promo->id ? 'border-[#A9552F] bg-[#A9552F]/10 font-bold' : 'border-[#E6DED2] hover:bg-[#FAF6EF]' }}">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="text-[#1F1A17] font-bold">{{ $promo->name }} ({{ $promo->code }})</span>
                                    <span class="px-2 py-0.5 rounded bg-[#A9552F] text-white text-[10px] font-bold uppercase whitespace-nowrap shrink-0">{{ $promo->rule_type->label() }}</span>
                                </div>
                                <div class="text-[#6F6259] mt-0.5">{{ $promo->description }}</div>
                            </a>
                        @endforeach

                        @foreach($activeVouchers as $voucher)
                            <a href="{{ route('checkout.index', ['reward_redemption_id' => $voucher->id]) }}" onclick="saveCheckoutDraft()"
                               class="block p-3 rounded-xl border transition {{ (int)$selectedVoucherId === (int)$voucher->id ? 'border-[#1F7A4C] bg-[#DCFCE7]/50 font-bold' : 'border-[#E6DED2] hover:bg-[#FAF6EF]' }}">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="text-[#1F1A17] font-bold">Voucher Reward: {{ $voucher->reward?->name }}</span>
                                    <span class="font-mono text-[11px] font-bold text-[#166534] whitespace-nowrap shrink-0">{{ $voucher->voucher_code }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="bg-[#F2ECE1] rounded-2xl border border-[#E6DED2] p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="text-xs text-[#3B2A20]">
                        <strong>Pesan sebagai Tamu (Guest).</strong> Masuk atau daftar Member untuk mendapatkan poin loyalti & memakai promo khusus.
                    </div>
                    <a href="{{ route('login') }}" class="shrink-0 min-h-[42px] inline-flex items-center justify-center px-4 py-2 rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-bold hover:bg-[#2A1E17] transition">
                        Masuk Member
                    </a>
                </div>
            @endif

            <!-- Step 2: Main Checkout Form -->
            <form id="checkoutForm" method="POST" action="{{ route('checkout.store') }}" onsubmit="sessionStorage.removeItem('774_checkout_draft'); this.querySelector('button[type=submit]').disabled=true;" class="space-y-4">
                @csrf
                <input type="hidden" name="client_request_id" value="{{ $clientRequestId }}">
                @if($selectedPromoId)
                    <input type="hidden" name="promotion_id" value="{{ $selectedPromoId }}">
                @endif
                @if($selectedVoucherId)
                    <input type="hidden" name="reward_redemption_id" value="{{ $selectedVoucherId }}">
                @endif

                <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-4">
                    <h2 class="text-sm font-extrabold text-[#1F1A17]">1. Informasi Pemesan & Meja</h2>

                    <!-- D7: Table number is pre-filled from QR token and shown as read-only -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#6F6259] mb-1">
                            Nomor Meja (Otomatis dari QR Meja)
                        </label>
                        <input type="text" value="{{ $table->label }} (Nomor {{ $table->number }})" readonly
                               class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#E6DED2] bg-[#F2ECE1] text-sm font-bold text-[#3B2A20] cursor-not-allowed">
                    </div>

                    <div>
                        <label for="customer_name" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1">
                            Nama Pemesan *
                        </label>
                        <input id="customer_name" name="customer_name" type="text" required minlength="2" maxlength="80" autocomplete="name"
                               value="{{ old('customer_name', auth()->user()?->name) }}"
                               placeholder="Masukkan nama Anda"
                               class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm">
                    </div>

                    <div>
                        <label for="customer_email" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1">
                            Alamat Email (Untuk Pengiriman Invoice Digital) *
                        </label>
                        <input id="customer_email" name="customer_email" type="email" required maxlength="120" autocomplete="email"
                               value="{{ old('customer_email', auth()->user()?->email) }}"
                               placeholder="nama@email.com"
                               class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm">
                    </div>

                    <div>
                        <label for="customer_phone" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1">
                            Nomor WhatsApp (Opsional)
                        </label>
                        <input id="customer_phone" name="customer_phone" type="tel" maxlength="30" autocomplete="tel"
                               value="{{ old('customer_phone', auth()->user()?->phone) }}"
                               placeholder="0812xxxxxxxx"
                               class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm">
                    </div>
                </div>

                <!-- Payment Method Choice (D5: Only Tunai and QRIS) -->
                <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-3">
                    <h2 class="text-sm font-extrabold text-[#1F1A17]">2. Pilih Metode Pembayaran</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-start gap-3 p-3.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] cursor-pointer hover:border-[#3B2A20] has-[:checked]:border-[#3B2A20] has-[:checked]:bg-[#F2ECE1]/70 transition">
                            <input type="radio" name="payment_method" value="qris" {{ old('payment_method', 'qris') === 'qris' ? 'checked' : '' }}
                                   class="mt-1 accent-[#3B2A20] shrink-0">
                            <div>
                                <div class="text-sm font-extrabold text-[#1F1A17]">QRIS Digital</div>
                                <div class="text-xs text-[#6F6259]">Pindai kode QRIS dan verifikasi otomatis.</div>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] cursor-pointer hover:border-[#3B2A20] has-[:checked]:border-[#3B2A20] has-[:checked]:bg-[#F2ECE1]/70 transition">
                            <input type="radio" name="payment_method" value="tunai" {{ old('payment_method') === 'tunai' ? 'checked' : '' }}
                                   class="mt-1 accent-[#3B2A20] shrink-0">
                            <div>
                                <div class="text-sm font-extrabold text-[#1F1A17]">Tunai di Kasir</div>
                                <div class="text-xs text-[#6F6259]">Bayar tunai langsung di meja Kasir 774 HUB.</div>
                            </div>
                        </label>
                    </div>
                </div>
            </form>
        </div>

        <!-- Order Summary (Sticky Right Column on Desktop/iPad Landscape) -->
        <div class="lg:col-span-5 lg:sticky lg:top-24 bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-2.5">
            <h2 class="text-sm font-extrabold text-[#1F1A17] mb-2">3. Ringkasan Pembayaran</h2>

            <div class="divide-y divide-[#F2ECE1] text-xs mb-2">
                @foreach($pricing['items'] as $item)
                    <div class="py-2 flex justify-between gap-3">
                        <div class="min-w-0">
                            <span class="font-bold text-[#1F1A17] break-words">{{ $item['quantity'] }}x {{ $item['product_name'] }}</span>
                            @if(!empty($item['note']))
                                <div class="text-[#A9552F] break-words">Catatan: "{{ $item['note'] }}"</div>
                            @endif
                        </div>
                        <span class="font-bold tabular-nums whitespace-nowrap shrink-0">Rp{{ number_format($item['line_total'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-between gap-2 text-sm text-[#6F6259]">
                <span>Subtotal</span>
                <span class="font-bold text-[#1F1A17] tabular-nums whitespace-nowrap">Rp{{ number_format($pricing['subtotal'], 0, ',', '.') }}</span>
            </div>

            @if($pricing['discount_amount'] > 0)
                <div class="flex justify-between gap-2 text-sm text-[#1F7A4C]">
                    <span>Potongan Promo / Voucher</span>
                    <span class="font-bold tabular-nums whitespace-nowrap">-Rp{{ number_format($pricing['discount_amount'], 0, ',', '.') }}</span>
                </div>
            @endif

            @if(($pricing['bonus_points'] ?? 0) > 0)
                <div class="flex justify-between gap-2 text-sm text-[#A9552F]">
                    <span>Bonus Poin Promo</span>
                    <span class="font-bold tabular-nums whitespace-nowrap">+{{ number_format($pricing['bonus_points'], 0, ',', '.') }} Poin</span>
                </div>
            @endif

            @if($pricing['tax_rate'] > 0)
                <div class="flex justify-between gap-2 text-sm text-[#6F6259]">
                    <span>Pajak ({{ $taxRateFormatted }}%)</span>
                    <span class="font-bold text-[#1F1A17] tabular-nums whitespace-nowrap">Rp{{ number_format($pricing['tax_amount'], 0, ',', '.') }}</span>
                </div>
            @endif

            <div class="pt-2.5 border-t border-[#E6DED2] flex justify-between gap-2 text-lg font-extrabold text-[#1F1A17]">
                <span>Total Tagihan</span>
                <span class="tabular-nums text-[#3B2A20] whitespace-nowrap">Rp{{ number_format($pricing['total_amount'], 0, ',', '.') }}</span>
            </div>

            <div class="pt-3">
                <button type="submit" form="checkoutForm" class="w-full min-h-[50px] rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-sm font-extrabold hover:bg-[#2A1E17] transition cursor-pointer">
                    Buat Pesanan & Lanjut ke Pembayaran →
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function saveCheckoutDraft() {
        try {
            const methodEl = document.querySelector('input[name="payment_method"]:checked');
            sessionStorage.setItem('774_checkout_draft', JSON.stringify({
                name: document.getElementById('customer_name')?.value || '',
                email: document.getElementById('customer_email')?.value || '',
                phone: document.getElementById('customer_phone')?.value || '',
                payment_method: methodEl ? methodEl.value : 'qris'
            }));
        } catch (e) {}
    }

    (function restoreCheckoutDraft() {
        try {
            const raw = sessionStorage.getItem('774_checkout_draft');
            if (!raw) return;
            const draft = JSON.parse(raw);
            const nameEl = document.getElementById('customer_name');
            const emailEl = document.getElementById('customer_email');
            const phoneEl = document.getElementById('customer_phone');
            if (nameEl && !nameEl.value && draft.name) nameEl.value = draft.name;
            if (emailEl && !emailEl.value && draft.email) emailEl.value = draft.email;
            if (phoneEl && !phoneEl.value && draft.phone) phoneEl.value = draft.phone;
            if (draft.payment_method) {
                const radio = document.querySelector(`input[name="payment_method"][value="${draft.payment_method}"]`);
                if (radio) radio.checked = true;
            }
        } catch (e) {}
    })();
</script>
@endsection
