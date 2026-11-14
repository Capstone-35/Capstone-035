@extends('layouts.customer', ['title' => 'Checkout Pesanan'])

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-extrabold text-[#1F1A17]">Checkout Pesanan</h1>
        <a href="{{ route('cart.index') }}" class="text-xs font-bold text-[#6F6259] hover:text-[#3B2A20]">
            ← Ubah Keranjang
        </a>
    </div>

    <!-- Step 1: Member Promo & Voucher Selector (D13, D14: At most one promo OR one reward per order) -->
    @if($member)
        <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm">
            <div class="flex items-center justify-between gap-2 mb-3">
                <div>
                    <h2 class="text-sm font-extrabold text-[#1F1A17]">Promo Behavioral & Voucher Reward Member</h2>
                    <p class="text-xs text-[#6F6259]">Maksimal 1 promo atau 1 voucher reward dapat dipilih per transaksi.</p>
                </div>
                <span class="px-2.5 py-1 rounded-full bg-[#F2ECE1] text-[#3B2A20] text-xs font-bold">
                    {{ number_format($member->points_balance, 0, ',', '.') }} Poin
                </span>
            </div>

            <div class="space-y-2 text-xs">
                <a href="{{ route('checkout.index') }}"
                   class="block p-3 rounded-xl border transition {{ !$selectedPromoId && !$selectedVoucherId ? 'border-[#3B2A20] bg-[#FAF6EF] font-bold' : 'border-[#E6DED2] hover:bg-[#FAF6EF]' }}">
                    Tanpa Promo / Voucher
                </a>

                @foreach($eligiblePromos as $promo)
                    <a href="{{ route('checkout.index', ['promotion_id' => $promo->id]) }}"
                       class="block p-3 rounded-xl border transition {{ (int)$selectedPromoId === (int)$promo->id ? 'border-[#A9552F] bg-[#A9552F]/10 font-bold' : 'border-[#E6DED2] hover:bg-[#FAF6EF]' }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[#1F1A17] font-bold">{{ $promo->name }} ({{ $promo->code }})</span>
                            <span class="px-2 py-0.5 rounded bg-[#A9552F] text-white text-[10px] font-bold uppercase">{{ $promo->rule_type->label() }}</span>
                        </div>
                        <div class="text-[#6F6259] mt-0.5">{{ $promo->description }}</div>
                    </a>
                @endforeach

                @foreach($activeVouchers as $voucher)
                    <a href="{{ route('checkout.index', ['reward_redemption_id' => $voucher->id]) }}"
                       class="block p-3 rounded-xl border transition {{ (int)$selectedVoucherId === (int)$voucher->id ? 'border-[#1F7A4C] bg-[#DCFCE7]/50 font-bold' : 'border-[#E6DED2] hover:bg-[#FAF6EF]' }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[#1F1A17] font-bold">Voucher Reward: {{ $voucher->reward?->name }}</span>
                            <span class="font-mono text-[11px] font-bold text-[#166534]">{{ $voucher->voucher_code }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-[#F2ECE1] rounded-2xl border border-[#E6DED2] p-4 flex items-center justify-between gap-3">
            <div class="text-xs text-[#3B2A20]">
                <strong>Pesan sebagai Tamu (Guest).</strong> Masuk atau daftar Member untuk mendapatkan poin loyalti & memakai promo khusus.
            </div>
            <a href="{{ route('login') }}" class="shrink-0 px-3 py-2 rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-bold">
                Masuk Member
            </a>
        </div>
    @endif

    <!-- Step 2: Main Checkout Form -->
    <form method="POST" action="{{ route('checkout.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="client_request_id" value="{{ $clientRequestId }}">
        @if($selectedPromoId)
            <input type="hidden" name="promotion_id" value="{{ $selectedPromoId }}">
        @endif
        @if($selectedVoucherId)
            <input type="hidden" name="reward_redemption_id" value="{{ $selectedVoucherId }}">
        @endif

        <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-4">
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
                <input id="customer_name" name="customer_name" type="text" required
                       value="{{ old('customer_name', auth()->user()?->name) }}"
                       placeholder="Masukkan nama Anda"
                       class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm">
            </div>

            <div>
                <label for="customer_email" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1">
                    Alamat Email (Untuk Pengiriman Invoice Digital) *
                </label>
                <input id="customer_email" name="customer_email" type="email" required
                       value="{{ old('customer_email', auth()->user()?->email) }}"
                       placeholder="nama@email.com"
                       class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm">
            </div>

            <div>
                <label for="customer_phone" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1">
                    Nomor WhatsApp (Opsional)
                </label>
                <input id="customer_phone" name="customer_phone" type="text"
                       value="{{ old('customer_phone', auth()->user()?->phone) }}"
                       placeholder="0812xxxxxxxx"
                       class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm">
            </div>
        </div>

        <!-- Payment Method Choice (D5: Only Tunai and QRIS) -->
        <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-3">
            <h2 class="text-sm font-extrabold text-[#1F1A17]">2. Pilih Metode Pembayaran</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="flex items-start gap-3 p-3.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] cursor-pointer hover:border-[#3B2A20]">
                    <input type="radio" name="payment_method" value="qris" {{ old('payment_method', 'qris') === 'qris' ? 'checked' : '' }}
                           class="mt-1 text-[#3B2A20] focus:ring-[#A9552F]">
                    <div>
                        <div class="text-sm font-extrabold text-[#1F1A17]">QRIS Digital</div>
                        <div class="text-xs text-[#6F6259]">Pindai kode QRIS dan verifikasi otomatis.</div>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-3.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] cursor-pointer hover:border-[#3B2A20]">
                    <input type="radio" name="payment_method" value="tunai" {{ old('payment_method') === 'tunai' ? 'checked' : '' }}
                           class="mt-1 text-[#3B2A20] focus:ring-[#A9552F]">
                    <div>
                        <div class="text-sm font-extrabold text-[#1F1A17]">Tunai di Kasir</div>
                        <div class="text-xs text-[#6F6259]">Bayar tunai langsung di meja Kasir 774 HUB.</div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-2.5">
            <h2 class="text-sm font-extrabold text-[#1F1A17] mb-2">3. Ringkasan Pembayaran</h2>

            <div class="divide-y divide-[#F2ECE1] text-xs mb-2">
                @foreach($pricing['items'] as $item)
                    <div class="py-2 flex justify-between gap-2">
                        <div>
                            <span class="font-bold text-[#1F1A17]">{{ $item['quantity'] }}x {{ $item['product_name'] }}</span>
                            @if(!empty($item['note']))
                                <div class="text-[#A9552F]">Catatan: "{{ $item['note'] }}"</div>
                            @endif
                        </div>
                        <span class="font-bold tabular-nums">Rp{{ number_format($item['line_total'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-between text-sm text-[#6F6259]">
                <span>Subtotal</span>
                <span class="font-bold text-[#1F1A17] tabular-nums">Rp{{ number_format($pricing['subtotal'], 0, ',', '.') }}</span>
            </div>

            @if($pricing['discount_amount'] > 0)
                <div class="flex justify-between text-sm text-[#1F7A4C]">
                    <span>Potongan Promo / Voucher</span>
                    <span class="font-bold tabular-nums">-Rp{{ number_format($pricing['discount_amount'], 0, ',', '.') }}</span>
                </div>
            @endif

            @if($pricing['tax_rate'] > 0)
                <div class="flex justify-between text-sm text-[#6F6259]">
                    <span>Pajak ({{ number_format($pricing['tax_rate'], 0) }}%)</span>
                    <span class="font-bold text-[#1F1A17] tabular-nums">Rp{{ number_format($pricing['tax_amount'], 0, ',', '.') }}</span>
                </div>
            @endif

            <div class="pt-2.5 border-t border-[#E6DED2] flex justify-between text-lg font-extrabold text-[#1F1A17]">
                <span>Total Tagihan</span>
                <span class="tabular-nums text-[#3B2A20]">Rp{{ number_format($pricing['total_amount'], 0, ',', '.') }}</span>
            </div>

            <div class="pt-3">
                <button type="submit" class="w-full min-h-[50px] rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-sm font-extrabold hover:bg-[#2A1E17] transition">
                    Buat Pesanan & Lanjut ke Pembayaran →
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
