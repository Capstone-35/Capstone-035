@extends('layouts.customer', ['title' => 'Keranjang Pesanan'])

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-extrabold text-[#1F1A17]">Keranjang Pesanan</h1>
        <a href="{{ route('menu.index') }}" class="text-xs font-bold text-[#A9552F] hover:underline">
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
        <div class="space-y-3">
            @foreach($cartData['lines'] as $line)
                <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="font-extrabold text-sm text-[#1F1A17]">{{ $line['product_name'] }}</h2>
                                @if(!$line['is_available'])
                                    <span class="px-2 py-0.5 rounded-full bg-[#FEE2E2] text-[#991B1B] text-[10px] font-bold">Baru Saja Habis</span>
                                @endif
                            </div>
                            <div class="text-xs text-[#6F6259] mt-0.5 tabular-nums">
                                Rp{{ number_format($line['unit_price'], 0, ',', '.') }} / porsi
                            </div>
                            @if($line['note'])
                                <div class="text-xs text-[#A9552F] font-medium mt-1">Catatan: "{{ $line['note'] }}"</div>
                            @endif
                        </div>

                        <div class="text-sm font-extrabold text-[#1F1A17] tabular-nums">
                            Rp{{ number_format($line['line_total'], 0, ',', '.') }}
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-t border-[#F2ECE1] flex flex-wrap items-center justify-between gap-2">
                        <form method="POST" action="{{ route('cart.items.update', ['lineKey' => $line['line_key']]) }}" class="flex items-center gap-2">
                            @csrf
                            @method('PATCH')
                            <label class="text-xs font-semibold text-[#6F6259]">Jml:</label>
                            <input type="number" name="quantity" min="0" max="99" value="{{ $line['quantity'] }}"
                                   class="w-16 min-h-[38px] px-2.5 py-1 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs font-bold tabular-nums">
                            <input type="text" name="note" value="{{ $line['note'] }}" placeholder="Catatan..."
                                   class="w-36 sm:w-48 min-h-[38px] px-2.5 py-1 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs">
                            <button type="submit" class="min-h-[38px] px-3 rounded-xl bg-[#F2ECE1] text-[#3B2A20] text-xs font-bold hover:bg-[#E6DED2]">
                                Simpan
                            </button>
                        </form>

                        <form method="POST" action="{{ route('cart.items.destroy', ['lineKey' => $line['line_key']]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="min-h-[38px] px-3 rounded-xl text-xs font-bold text-[#B91C1C] hover:bg-[#FEE2E2]">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-2.5">
            <div class="flex justify-between text-sm text-[#6F6259]">
                <span>Subtotal ({{ $cartData['item_count'] }} item)</span>
                <span class="font-bold text-[#1F1A17] tabular-nums">Rp{{ number_format($cartData['subtotal'], 0, ',', '.') }}</span>
            </div>
            @if($cartData['tax_rate'] > 0)
                <div class="flex justify-between text-sm text-[#6F6259]">
                    <span>Estimasi Pajak ({{ number_format($cartData['tax_rate'], 0) }}%)</span>
                    <span class="font-bold text-[#1F1A17] tabular-nums">Rp{{ number_format($cartData['tax_amount'], 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="pt-2 border-t border-[#E6DED2] flex justify-between text-base font-extrabold text-[#1F1A17]">
                <span>Estimasi Total</span>
                <span class="tabular-nums">Rp{{ number_format($cartData['total_amount'], 0, ',', '.') }}</span>
            </div>

            @if(!$table)
                <div class="mt-3 p-3 rounded-xl bg-amber-50 border border-amber-300 text-xs text-amber-950 font-semibold">
                    Silakan pilih atau pindai QR meja terlebih dahulu di halaman Menu sebelum melanjutkan ke Checkout.
                </div>
            @endif

            <div class="pt-3">
                <a href="{{ route('checkout.index') }}"
                   class="w-full min-h-[48px] flex items-center justify-center rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-sm font-extrabold hover:bg-[#2A1E17] transition">
                    Lanjut ke Checkout →
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
