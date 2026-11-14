@extends('layouts.customer', ['title' => 'Menu Katalog'])

@section('content')
<div class="space-y-4">
    @if(!$table)
        @php
            $availableTables = \App\Models\DiningTable::query()->where('is_active', true)->orderBy('number')->get();
        @endphp
        <div class="rounded-2xl bg-amber-50 border border-amber-300 p-4 text-xs text-amber-950">
            <div class="font-bold mb-1.5">Anda belum memindai QR Meja. Pilih meja Anda untuk memulai pemesanan:</div>
            <div class="flex flex-wrap gap-1.5">
                @foreach($availableTables as $t)
                    <a href="{{ route('table.scan', ['qr_token' => $t->qr_token]) }}"
                       class="px-3 py-1.5 rounded-lg bg-white border border-amber-300 hover:bg-[#3B2A20] hover:text-white font-bold transition">
                        {{ $t->label }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Search Bar -->
    <form method="GET" action="{{ route('menu.index') }}" class="flex gap-2">
        @if($selectedCategory)
            <input type="hidden" name="category" value="{{ $selectedCategory }}">
        @endif
        <input type="search" name="q" value="{{ $search }}" placeholder="Cari kopi, minuman, makanan..."
               aria-label="Cari menu"
               class="flex-1 min-h-[44px] px-4 py-2 rounded-xl bg-white border border-[#D6CBBC] text-sm focus:outline-none focus:ring-2 focus:ring-[#A9552F]">
        <button type="submit" class="min-h-[44px] px-4 rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-bold">
            Cari
        </button>
    </form>

    <!-- Category Filter Chips -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1">
        <a href="{{ route('menu.index', $search ? ['q' => $search] : []) }}"
           class="min-h-[40px] px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ empty($selectedCategory) ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-white border border-[#E6DED2] text-[#6F6259]' }}">
            Semua Menu
        </a>
        @foreach($categories as $category)
            <a href="{{ route('menu.index', array_filter(['category' => $category->slug, 'q' => $search])) }}"
               class="min-h-[40px] px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $selectedCategory === $category->slug || (string)$selectedCategory === (string)$category->id ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-white border border-[#E6DED2] text-[#6F6259]' }}">
                {{ $category->name }}
            </a>
        @endforeach
    </div>

    <!-- Product Grid -->
    @if($products->isEmpty())
        <x-empty-state
            title="Menu Tidak Ditemukan"
            description="Tidak ada produk yang cocok dengan pencarian atau filter kategori ini."
            :action-url="route('menu.index')"
            action-label="Lihat Semua Menu"
        />
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            @foreach($products as $product)
                <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 flex flex-col justify-between shadow-sm {{ !$product->is_available ? 'opacity-65' : '' }}">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#A9552F]">
                                {{ $product->category?->name }}
                            </span>
                            @if(!$product->is_available)
                                <span class="px-2 py-0.5 rounded-full bg-[#FEE2E2] text-[#991B1B] text-[11px] font-bold">
                                    Habis
                                </span>
                            @endif
                        </div>

                        <a href="{{ route('menu.show', ['product' => $product->slug]) }}" class="block mt-1">
                            <h2 class="text-base font-extrabold text-[#1F1A17] hover:text-[#A9552F] transition">
                                {{ $product->name }}
                            </h2>
                        </a>

                        @if($product->description)
                            <p class="mt-1 text-xs text-[#6F6259] line-clamp-2">
                                {{ $product->description }}
                            </p>
                        @endif
                    </div>

                    <div class="mt-4 pt-3 border-t border-[#F2ECE1] flex items-center justify-between gap-2">
                        <div class="text-base font-extrabold text-[#1F1A17] tabular-nums">
                            {{ $product->formattedPrice() }}
                        </div>

                        @if($product->is_available)
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('menu.show', ['product' => $product->slug]) }}"
                                   class="min-h-[40px] px-2.5 inline-flex items-center justify-center rounded-xl border border-[#D6CBBC] text-xs font-semibold text-[#3B2A20] hover:bg-[#F2ECE1]">
                                    Catatan
                                </a>
                                <form method="POST" action="{{ route('cart.items.store') }}">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="min-h-[40px] px-3.5 inline-flex items-center justify-center rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-bold hover:bg-[#2A1E17] transition">
                                        + Tambah
                                    </button>
                                </form>
                            </div>
                        @else
                            <button type="button" disabled class="min-h-[40px] px-3.5 rounded-xl bg-gray-200 text-gray-500 text-xs font-bold cursor-not-allowed">
                                Tidak Tersedia
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Floating Checkout Bar when Cart has items -->
    @if($cartSummary['item_count'] > 0)
        <div class="sticky bottom-20 z-20 mt-4">
            <a href="{{ route('cart.index') }}" class="flex items-center justify-between px-5 py-3.5 rounded-2xl bg-[#3B2A20] text-[#FAF6EF] shadow-lg hover:bg-[#2A1E17] transition">
                <div>
                    <div class="text-xs font-semibold text-[#FAF6EF]/80">{{ $cartSummary['item_count'] }} Item di Keranjang</div>
                    <div class="text-base font-extrabold tabular-nums">Rp{{ number_format($cartSummary['subtotal'], 0, ',', '.') }}</div>
                </div>
                <span class="px-4 py-2 rounded-xl bg-[#A9552F] text-white text-xs font-extrabold">
                    Lihat Keranjang & Checkout →
                </span>
            </a>
        </div>
    @endif
</div>
@endsection
