@extends('layouts.customer', ['title' => 'Keranjang Pesanan'])

@section('content')
@php
    $taxRateFormatted = rtrim(rtrim(number_format((float) ($cartData['tax_rate'] ?? 0), 2, '.', ''), '0'), '.');
@endphp
<div class="max-w-5xl mx-auto space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-lg sm:text-xl font-extrabold text-[#1F1A17]">Keranjang Pesanan</h1>
        <a href="{{ route('menu.index') }}" class="min-h-[40px] inline-flex items-center px-3 rounded-xl bg-[#F2ECE1] text-xs font-bold text-[#A9552F] hover:bg-[#E6DED2] transition">
            + Tambah Menu Lain
        </a>
    </div>

    @if(empty($cartData['lines']))
        <x-empty-state
            title="Keranjang Anda Masih Kosong"
            description="Pilih kopi, minuman, atau camilan favorit Anda dari katalog menu 774 HUB."
            :action-url="route('menu.index')"
            action-label="Jelajahi Menu"
        />
    @else
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
            <div class="lg:col-span-7 xl:col-span-8 space-y-3">
                @foreach($cartData['lines'] as $line)
                    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="font-extrabold text-sm text-[#1F1A17] break-words">{{ $line['product_name'] }}</h2>
                                    @if(!$line['is_available'])
                                        <span class="px-2 py-0.5 rounded-full bg-[#FEE2E2] text-[#991B1B] text-[10px] font-bold whitespace-nowrap shrink-0">Baru Saja Habis</span>
                                    @endif
                                </div>
                                <div class="text-xs text-[#6F6259] mt-0.5 tabular-nums">
                                    Rp{{ number_format($line['unit_price'], 0, ',', '.') }} / porsi
                                </div>
                                @if($line['note'])
                                    <div class="text-xs text-[#A9552F] font-medium mt-1 break-words">Catatan: "{{ $line['note'] }}"</div>
                                @endif
                            </div>

                            <div class="text-sm font-extrabold text-[#1F1A17] tabular-nums whitespace-nowrap shrink-0">
                                Rp{{ number_format($line['line_total'], 0, ',', '.') }}
                            </div>
                        </div>

                        <div class="mt-3 pt-3 border-t border-[#F2ECE1] flex flex-wrap items-center justify-between gap-2">
                            <form method="POST" action="{{ route('cart.items.update', ['lineKey' => $line['line_key']]) }}" class="flex flex-wrap items-center gap-2 flex-1 min-w-0">
                                @csrf
                                @method('PATCH')
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <label class="text-xs font-semibold text-[#6F6259]">Jml:</label>
                                    <input type="number" name="quantity" min="0" max="99" value="{{ $line['quantity'] }}"
                                           class="w-16 min-h-[42px] px-2.5 py-1 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs font-bold tabular-nums">
                                </div>
                                <input type="text" name="note" maxlength="200" value="{{ $line['note'] }}" placeholder="Catatan..."
                                       class="flex-1 min-w-[120px] min-h-[42px] px-2.5 py-1 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs">
                                <button type="submit" class="min-h-[42px] px-3.5 rounded-xl bg-[#F2ECE1] text-[#3B2A20] text-xs font-bold hover:bg-[#E6DED2] transition shrink-0">
                                    Simpan
                                </button>
                            </form>

                            <form method="POST" action="{{ route('cart.items.destroy', ['lineKey' => $line['line_key']]) }}" class="shrink-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="min-h-[42px] px-3 rounded-xl text-xs font-bold text-[#B91C1C] hover:bg-[#FEE2E2] transition">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="lg:col-span-5 xl:col-span-4 lg:sticky lg:top-24 bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-2.5">
                <h2 class="text-sm font-extrabold text-[#1F1A17] pb-2 border-b border-[#F2ECE1]">Ringkasan Keranjang</h2>
                <div class="flex justify-between gap-2 text-sm text-[#6F6259]">
                    <span>Subtotal ({{ $cartData['item_count'] }} item)</span>
                    <span class="font-bold text-[#1F1A17] tabular-nums whitespace-nowrap">Rp{{ number_format($cartData['subtotal'], 0, ',', '.') }}</span>
                </div>
                @if($cartData['tax_rate'] > 0)
                    <div class="flex justify-between gap-2 text-sm text-[#6F6259]">
                        <span>Estimasi Pajak ({{ $taxRateFormatted }}%)</span>
                        <span class="font-bold text-[#1F1A17] tabular-nums whitespace-nowrap">Rp{{ number_format($cartData['tax_amount'], 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="pt-2 border-t border-[#E6DED2] flex justify-between gap-2 text-base font-extrabold text-[#1F1A17]">
                    <span>Estimasi Total</span>
                    <span class="tabular-nums whitespace-nowrap">Rp{{ number_format($cartData['total_amount'], 0, ',', '.') }}</span>
                </div>

                @if(!$table)
                    <div class="mt-3 p-3 rounded-xl bg-amber-50 border border-amber-300 text-xs text-amber-950 font-semibold">
                        Silakan pilih atau pindai QR meja terlebih dahulu di halaman Menu sebelum melanjutkan ke Checkout.
                    </div>
                @endif

                @if($cartData['has_unavailable'])
                    <div class="mt-2 p-3 rounded-xl bg-[#FEE2E2] border border-[#FCA5A5] text-xs text-[#991B1B] font-semibold">
                        Terdapat menu yang baru saja habis. Silakan hapus item bertanda habis terlebih dahulu.
                    </div>
                @endif

                <div class="pt-3">
                    @if($table && !$cartData['has_unavailable'])
                        <a href="{{ route('checkout.index') }}"
                           class="w-full min-h-[48px] flex items-center justify-center rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-sm font-extrabold hover:bg-[#2A1E17] transition">
                            Lanjut ke Checkout →
                        </a>
                    @else
                        <button type="button" disabled
                                class="w-full min-h-[48px] flex items-center justify-center rounded-xl bg-gray-200 text-gray-500 text-sm font-extrabold cursor-not-allowed">
                            Lanjut ke Checkout →
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
