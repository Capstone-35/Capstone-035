@extends('layouts.customer', ['title' => $product->name])

@section('content')
<div class="bg-white rounded-2xl border border-[#E6DED2] p-6 shadow-sm">
    <a href="{{ route('menu.index') }}" class="inline-flex items-center text-xs font-bold text-[#6F6259] hover:text-[#3B2A20] mb-4">
        ← Kembali ke Katalog Menu
    </a>

    <div class="flex items-start justify-between gap-3">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-[#A9552F]">{{ $product->category?->name }}</span>
            <h1 class="text-2xl font-extrabold text-[#1F1A17] mt-1">{{ $product->name }}</h1>
        </div>
        <div class="text-xl font-extrabold text-[#3B2A20] tabular-nums">
            {{ $product->formattedPrice() }}
        </div>
    </div>

    @if($product->description)
        <p class="mt-3 text-sm text-[#6F6259] leading-relaxed">
            {{ $product->description }}
        </p>
    @endif

    @if($product->is_available)
        <form method="POST" action="{{ route('cart.items.store') }}" class="mt-6 pt-5 border-t border-[#E6DED2] space-y-4">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">

            <div>
                <label for="quantity" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1.5">
                    Jumlah Pesanan
                </label>
                <input id="quantity" name="quantity" type="number" min="1" max="99" value="1" required
                       class="w-32 min-h-[44px] px-3.5 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-base font-bold tabular-nums">
            </div>

            <div>
                <label for="note" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1.5">
                    Catatan Khusus (Opsional — mis. Less Sugar, Less Ice, Pedas)
                </label>
                <input id="note" name="note" type="text" maxlength="200" value="{{ old('note') }}"
                       placeholder="Contoh: Less sugar, es sedikit"
                       class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm">
            </div>

            <button type="submit" class="w-full min-h-[48px] rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-sm font-bold hover:bg-[#2A1E17] transition">
                + Tambahkan ke Keranjang
            </button>
        </form>
    @else
        <div class="mt-6 p-4 rounded-xl bg-[#FEE2E2] text-[#991B1B] text-sm font-bold text-center">
            Maaf, menu ini sedang habis dan tidak dapat dipesan saat ini.
        </div>
    @endif
</div>
@endsection
