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
    @elseif(!auth()->check())
        <div class="rounded-2xl bg-[#F2ECE1] border border-[#E6DED2] p-3.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
            <div class="text-[#6F6259]">
                <strong class="text-[#1F1A17]">Pesan sebagai Tamu di {{ $table->label }}.</strong> Masuk akun Member untuk mengumpulkan poin & klaim promo.
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button"
                        onclick="openMemberModal()"
                        class="px-3 py-1.5 rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-bold hover:bg-[#2A1E17] transition">
                    Masuk Member
                </button>
                <a href="{{ route('register') }}" class="px-3 py-1.5 rounded-xl border border-[#3B2A20] text-[#3B2A20] text-xs font-bold hover:bg-[#E6DED2] transition">
                    Daftar
                </a>
            </div>
        </div>
    @endif

    @php
        $productsByCategory = $products->groupBy('category_id');
        $activeCategories = $categories->filter(fn ($cat) => $productsByCategory->has($cat->id))->values();
        $displayCategories = $activeCategories->isNotEmpty() ? $activeCategories : $categories;
    @endphp

    <!-- Search Bar -->
    <form method="GET" action="{{ route('menu.index') }}" class="flex gap-2">
        <input type="search" name="q" value="{{ $search }}" placeholder="Cari kopi, minuman, makanan..."
               aria-label="Cari menu"
               class="flex-1 min-w-0 min-h-[44px] px-4 py-2 rounded-xl bg-white border border-[#D6CBBC] text-sm focus:outline-none focus:ring-2 focus:ring-[#A9552F]">
        @if($search !== '')
            <a href="{{ route('menu.index') }}"
               class="min-h-[44px] px-3 inline-flex items-center justify-center rounded-xl border border-[#D6CBBC] bg-white text-[#6F6259] text-xs font-bold hover:bg-[#F2ECE1] transition shrink-0">
                Reset
            </a>
        @endif
        <button type="submit" class="min-h-[44px] px-4 sm:px-5 rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-bold shrink-0 hover:bg-[#2A1E17] transition">
            Cari
        </button>
    </form>

    <!-- Sticky Category Filter Chips (Scrollspy Navigation) -->
    @if($products->isNotEmpty())
        <div id="category-nav-wrapper" class="sticky top-16 z-20 -mx-3.5 sm:-mx-6 lg:-mx-8 px-3.5 sm:px-6 lg:px-8 py-2.5 bg-[#FAF6EF]/95 backdrop-blur border-b border-[#E6DED2]/80 transition-shadow">
            <div id="category-chips-container" class="relative flex items-center gap-2 overflow-x-auto pb-0.5 scroll-smooth no-scrollbar">
                @if(($featuredProducts ?? collect())->isNotEmpty() || ($featuredPromotions ?? collect())->isNotEmpty())
                    @php
                        $isFeaturedSelected = empty($selectedCategory) || $selectedCategory === 'menu-andalan';
                    @endphp
                    <a href="#category-menu-andalan"
                       data-category-chip="menu-andalan"
                       aria-current="{{ $isFeaturedSelected ? 'true' : 'false' }}"
                       class="category-chip min-h-[44px] px-4 py-2 rounded-xl border text-xs font-bold whitespace-nowrap transition inline-flex items-center gap-1.5 shrink-0 {{ $isFeaturedSelected ? 'bg-[#3B2A20] text-[#FAF6EF] border-[#3B2A20] shadow-xs' : 'bg-white border-[#E6DED2] text-[#6F6259] hover:border-[#3B2A20]/40 hover:text-[#1F1A17]' }}">
                        <svg class="w-3.5 h-3.5 text-[#A9552F] shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <span>Menu Andalan & Promo</span>
                    </a>
                @endif
                @foreach($activeCategories as $category)
                    @php
                        $isSelected = $selectedCategory === $category->slug || (string) $selectedCategory === (string) $category->id;
                    @endphp
                    <a href="#category-{{ $category->slug }}"
                       data-category-chip="{{ $category->slug }}"
                       data-category-id="{{ $category->id }}"
                       aria-current="{{ $isSelected ? 'true' : 'false' }}"
                       class="category-chip min-h-[44px] px-4 py-2 rounded-xl border text-xs font-bold whitespace-nowrap transition inline-flex items-center gap-1.5 shrink-0 {{ $isSelected ? 'bg-[#3B2A20] text-[#FAF6EF] border-[#3B2A20] shadow-xs' : 'bg-white border-[#E6DED2] text-[#6F6259] hover:border-[#3B2A20]/40 hover:text-[#1F1A17]' }}">
                        <span>{{ $category->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Product Sections Grouped by Category -->
    @if($products->isEmpty())
        <x-empty-state
            title="Menu Tidak Ditemukan"
            description="Tidak ada produk yang cocok dengan pencarian atau filter kategori ini."
            :action-url="route('menu.index')"
            action-label="Reset Pencarian"
        />
    @else
        <div id="menu-catalog-top" class="space-y-8 pt-1">
            <!-- Section: Menu Andalan & Promo Spesial -->
            @if(($featuredProducts ?? collect())->isNotEmpty() || ($featuredPromotions ?? collect())->isNotEmpty())
                <section id="category-menu-andalan"
                         data-category-section="menu-andalan"
                         class="scroll-mt-32">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3.5 pb-2 border-b border-[#E6DED2]">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-1.5 h-4 rounded-full bg-[#A9552F] shrink-0"></span>
                            <h2 class="text-sm sm:text-base font-extrabold text-[#1F1A17] tracking-tight">
                                Menu Andalan & Promo
                            </h2>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full bg-[#FEF3C7] border border-[#FCD34D] text-[11px] font-extrabold text-[#92400E] whitespace-nowrap shrink-0">
                            ★ Favorit Pelanggan
                        </span>
                    </div>

                    @if(($featuredPromotions ?? collect())->isNotEmpty() && $search === '')
                        <div class="mb-4 rounded-2xl bg-gradient-to-r from-[#3B2A20] to-[#523A2C] text-[#FAF6EF] p-3.5 sm:p-4 shadow-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-2.5">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-full bg-[#A9552F] text-white text-[10px] font-extrabold uppercase tracking-wider">
                                        Promo Aktif
                                    </span>
                                    <span class="text-xs font-bold text-[#FAF6EF]/90">Penawaran Spesial Hari Ini</span>
                                </div>
                                <a href="{{ route('promotions.public') }}" class="text-[11px] font-bold text-[#FCD34D] hover:underline whitespace-nowrap shrink-0">
                                    Lihat Semua →
                                </a>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">
                                @foreach($featuredPromotions as $promo)
                                    <a href="{{ route('promotions.public') }}"
                                       class="rounded-xl bg-white/10 hover:bg-white/15 border border-white/15 p-3 transition flex flex-col justify-between">
                                        <div>
                                            <div class="text-[11px] font-extrabold text-[#FCD34D]">
                                                {{ $promo->benefitLabel() }}
                                            </div>
                                            <div class="text-xs font-bold text-white line-clamp-1 mt-0.5">
                                                {{ $promo->name }}
                                            </div>
                                        </div>
                                        @if($promo->min_transaction > 0)
                                            <div class="mt-1.5 text-[10px] text-[#FAF6EF]/75">
                                                Min. transaksi Rp{{ number_format($promo->min_transaction, 0, ',', '.') }}
                                            </div>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(($featuredProducts ?? collect())->isNotEmpty())
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 sm:gap-4">
                            @foreach($featuredProducts as $product)
                                <div class="bg-white rounded-2xl border-2 border-[#E6DED2] p-4 flex flex-col justify-between shadow-sm relative overflow-hidden">
                                    <div>
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#A9552F] truncate">
                                                {{ $product->category?->name }}
                                            </span>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[#FEF3C7] text-[#92400E] text-[10px] font-extrabold whitespace-nowrap shrink-0">
                                                ★ Andalan
                                            </span>
                                        </div>

                                        <a href="{{ route('menu.show', ['product' => $product->slug]) }}" class="block mt-1">
                                            <h3 class="text-base font-extrabold text-[#1F1A17] hover:text-[#A9552F] transition">
                                                {{ $product->name }}
                                            </h3>
                                        </a>

                                        @if($product->description)
                                            <p class="mt-1 text-xs text-[#6F6259] line-clamp-2">
                                                {{ $product->description }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="mt-4 pt-3 border-t border-[#F2ECE1] flex flex-wrap items-center justify-between gap-2">
                                        <div class="text-base font-extrabold text-[#1F1A17] tabular-nums whitespace-nowrap">
                                            {{ $product->formattedPrice() }}
                                        </div>

                                        @if($product->is_available)
                                            <div class="flex items-center gap-1.5 shrink-0">
                                                <a href="{{ route('menu.show', ['product' => $product->slug]) }}"
                                                   class="min-h-[42px] px-2.5 inline-flex items-center justify-center rounded-xl border border-[#D6CBBC] text-xs font-semibold text-[#3B2A20] hover:bg-[#F2ECE1] whitespace-nowrap">
                                                    Catatan
                                                </a>
                                                <form method="POST" action="{{ route('cart.items.store') }}" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                                                    @csrf
                                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                    <input type="hidden" name="quantity" value="1">
                                                    <button type="submit" class="min-h-[42px] px-3.5 inline-flex items-center justify-center rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-bold hover:bg-[#2A1E17] transition whitespace-nowrap">
                                                        + Tambah
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif

            @foreach($activeCategories as $category)
                @php
                    $categoryProducts = $productsByCategory->get($category->id, collect());
                @endphp
                <section id="category-{{ $category->slug }}"
                         data-category-section="{{ $category->slug }}"
                         data-category-id="{{ $category->id }}"
                         class="scroll-mt-32">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3.5 pb-2 border-b border-[#E6DED2]">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-1.5 h-4 rounded-full bg-[#A9552F] shrink-0"></span>
                            <h2 class="text-sm sm:text-base font-extrabold text-[#1F1A17] tracking-tight">
                                {{ $category->name }}
                            </h2>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full bg-[#F2ECE1] border border-[#E6DED2] text-[11px] font-bold text-[#6F6259] whitespace-nowrap shrink-0">
                            {{ $categoryProducts->count() }} menu
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 sm:gap-4">
                        @foreach($categoryProducts as $product)
                            <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 flex flex-col justify-between shadow-sm {{ !$product->is_available ? 'opacity-65' : '' }}">
                                <div>
                                    <div class="flex items-start justify-between gap-2">
                                        <span class="text-[11px] font-bold uppercase tracking-wider text-[#A9552F] truncate">
                                            {{ $product->category?->name }}
                                        </span>
                                        @if(!$product->is_available)
                                            <span class="px-2 py-0.5 rounded-full bg-[#FEE2E2] text-[#991B1B] text-[11px] font-bold whitespace-nowrap shrink-0">
                                                Habis
                                            </span>
                                        @endif
                                    </div>

                                    <a href="{{ route('menu.show', ['product' => $product->slug]) }}" class="block mt-1">
                                        <h3 class="text-base font-extrabold text-[#1F1A17] hover:text-[#A9552F] transition">
                                            {{ $product->name }}
                                        </h3>
                                    </a>

                                    @if($product->description)
                                        <p class="mt-1 text-xs text-[#6F6259] line-clamp-2">
                                            {{ $product->description }}
                                        </p>
                                    @endif
                                </div>

                                <div class="mt-4 pt-3 border-t border-[#F2ECE1] flex flex-wrap items-center justify-between gap-2">
                                    <div class="text-base font-extrabold text-[#1F1A17] tabular-nums whitespace-nowrap">
                                        {{ $product->formattedPrice() }}
                                    </div>

                                    @if($product->is_available)
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <a href="{{ route('menu.show', ['product' => $product->slug]) }}"
                                               class="min-h-[42px] px-2.5 inline-flex items-center justify-center rounded-xl border border-[#D6CBBC] text-xs font-semibold text-[#3B2A20] hover:bg-[#F2ECE1] whitespace-nowrap">
                                                Catatan
                                            </a>
                                            <form method="POST" action="{{ route('cart.items.store') }}" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="min-h-[42px] px-3.5 inline-flex items-center justify-center rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-bold hover:bg-[#2A1E17] transition whitespace-nowrap">
                                                    + Tambah
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <button type="button" disabled class="min-h-[42px] px-3.5 rounded-xl bg-gray-200 text-gray-500 text-xs font-bold cursor-not-allowed whitespace-nowrap">
                                            Tidak Tersedia
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif

    <!-- Floating Checkout Bar when Cart has items -->
    @if($cartSummary['item_count'] > 0)
        <div class="sticky bottom-18 md:bottom-5 z-20 mt-4 max-w-2xl mx-auto">
            <a href="{{ route('cart.index') }}" class="flex flex-wrap sm:flex-nowrap items-center justify-between gap-2.5 px-4 sm:px-5 py-3.5 rounded-2xl bg-[#3B2A20] text-[#FAF6EF] shadow-lg hover:bg-[#2A1E17] transition">
                <div class="min-w-0">
                    <div class="text-xs font-semibold text-[#FAF6EF]/80">{{ $cartSummary['item_count'] }} Item di Keranjang</div>
                    <div class="text-base font-extrabold tabular-nums">Rp{{ number_format($cartSummary['subtotal'], 0, ',', '.') }}</div>
                </div>
                <span class="px-3.5 sm:px-4 py-2 rounded-xl bg-[#A9552F] text-white text-xs font-extrabold whitespace-nowrap shrink-0">
                    Keranjang & Checkout →
                </span>
            </a>
        </div>
    @endif
</div>

@if(!auth()->check())
    <!-- Modal Pop-up Login / Daftar Member (Mobile Optimized) -->
    <div id="member-prompt-modal"
         class="{{ ($showMemberModal ?? false) ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center p-4 bg-black/65 backdrop-blur-sm transition-opacity duration-200"
         style="background-color: rgba(0, 0, 0, 0.65);"
         role="dialog"
         aria-modal="true"
         aria-labelledby="modal-title">
        <div class="relative w-full max-w-[340px] sm:max-w-[380px] bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-2xl text-center"
             style="max-width: min(90vw, 380px);">
            <!-- Tombol Tutup X -->
            <button type="button"
                    onclick="closeMemberModal()"
                    aria-label="Tutup"
                    class="absolute top-3 right-3 w-8 h-8 rounded-full bg-[#FAF6EF] hover:bg-[#F2ECE1] text-[#6F6259] flex items-center justify-center transition focus:outline-none">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <!-- Status QR Meja -->
            @if($table)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-[#DCFCE7] text-[#166534] text-[11px] font-extrabold">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#166534]"></span>
                    QR Meja Terverifikasi • {{ $table->label }}
                </span>
            @endif

            <h2 id="modal-title" class="mt-2.5 text-lg font-extrabold text-[#1F1A17]">
                Selamat Datang di 774 HUB
            </h2>
            <p class="mt-1 text-xs text-[#6F6259] leading-relaxed">
                Punya akun Member? Masuk untuk mengumpulkan poin loyalti & menikmati promo diskon eksklusif.
            </p>

            <div class="mt-4 space-y-2">
                <a href="{{ route('login') }}"
                   class="flex items-center justify-center w-full min-h-[44px] rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs sm:text-sm font-bold hover:bg-[#2A1E17] transition shadow-sm">
                    Masuk sebagai Member
                </a>
                <a href="{{ route('register') }}"
                   class="flex items-center justify-center w-full min-h-[44px] rounded-xl bg-[#A9552F] text-white text-xs sm:text-sm font-bold hover:bg-[#8E4423] transition shadow-sm">
                    Daftar Member Baru
                </a>
                <button type="button"
                        onclick="closeMemberModal()"
                        class="w-full min-h-[42px] rounded-xl border border-[#D6CBBC] text-[#3B2A20] text-xs font-bold hover:bg-[#FAF6EF] transition">
                    Lanjut Pesan sebagai Tamu (Guest) →
                </button>
            </div>
        </div>
    </div>

    <script>
        function openMemberModal() {
            const modal = document.getElementById('member-prompt-modal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeMemberModal() {
            const modal = document.getElementById('member-prompt-modal');
            if (modal) {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMemberModal();
            }
        });

        document.getElementById('member-prompt-modal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeMemberModal();
            }
        });
    </script>
@endif

<script>
    (function () {
        const navWrapper = document.getElementById('category-nav-wrapper');
        const chipsContainer = document.getElementById('category-chips-container');
        const chips = Array.from(document.querySelectorAll('[data-category-chip]'));
        const sections = Array.from(document.querySelectorAll('[data-category-section]'));

        if (chips.length === 0 || sections.length === 0) {
            return;
        }

        const ACTIVE_CLASSES = ['bg-[#3B2A20]', 'text-[#FAF6EF]', 'border-[#3B2A20]', 'shadow-xs'];
        const INACTIVE_CLASSES = ['bg-white', 'border-[#E6DED2]', 'text-[#6F6259]', 'hover:border-[#3B2A20]/40', 'hover:text-[#1F1A17]'];

        let currentActiveSlug = null;
        let isProgrammaticScroll = false;
        let scrollLockTimer = null;
        let ticking = false;

        function getStickyOffset() {
            const header = document.querySelector('header');
            const headerHeight = header ? header.offsetHeight : 64;
            const navHeight = navWrapper ? navWrapper.offsetHeight : 56;
            return headerHeight + navHeight + 12;
        }

        function setActiveChip(slug, scrollChipBar = true) {
            if (currentActiveSlug === slug) {
                return;
            }
            currentActiveSlug = slug;

            chips.forEach((chip) => {
                const chipSlug = chip.getAttribute('data-category-chip');
                const isActive = chipSlug === slug;

                chip.setAttribute('aria-current', isActive ? 'true' : 'false');

                if (isActive) {
                    chip.classList.remove(...INACTIVE_CLASSES);
                    chip.classList.add(...ACTIVE_CLASSES);

                    if (scrollChipBar && chipsContainer) {
                        const targetLeft = chip.offsetLeft - (chipsContainer.clientWidth / 2) + (chip.offsetWidth / 2);
                        chipsContainer.scrollTo({
                            left: Math.max(0, targetLeft),
                            behavior: 'smooth'
                        });
                    }
                } else {
                    chip.classList.remove(...ACTIVE_CLASSES);
                    chip.classList.add(...INACTIVE_CLASSES);
                }
            });
        }

        function scrollToCategory(slug, smooth = true) {
            isProgrammaticScroll = true;
            if (scrollLockTimer) {
                clearTimeout(scrollLockTimer);
            }

            setActiveChip(slug, true);

            let targetY = 0;
            const targetSection = document.querySelector(`[data-category-section="${slug}"]`);
            if (targetSection) {
                targetY = Math.max(0, targetSection.getBoundingClientRect().top + window.scrollY - getStickyOffset());
            }

            window.scrollTo({
                top: targetY,
                behavior: smooth ? 'smooth' : 'auto'
            });

            scrollLockTimer = setTimeout(() => {
                isProgrammaticScroll = false;
            }, smooth ? 750 : 100);
        }

        chips.forEach((chip) => {
            chip.addEventListener('click', function (e) {
                e.preventDefault();
                const slug = this.getAttribute('data-category-chip');
                if (slug) {
                    scrollToCategory(slug, true);
                }
            });
        });

        function updateScrollspy() {
            if (navWrapper) {
                if (window.scrollY > 20) {
                    navWrapper.classList.add('shadow-xs');
                } else {
                    navWrapper.classList.remove('shadow-xs');
                }
            }

            if (isProgrammaticScroll) {
                return;
            }

            // At the very bottom of the page (only when user actually scrolled), highlight the last section
            const isAtBottom = window.scrollY > 80 && (window.innerHeight + window.scrollY) >= (document.documentElement.scrollHeight - 25);
            if (isAtBottom && sections.length > 0) {
                const lastSlug = sections[sections.length - 1].getAttribute('data-category-section');
                if (lastSlug) {
                    setActiveChip(lastSlug, true);
                    return;
                }
            }

            const triggerLine = getStickyOffset() + 40;
            let activeSlug = sections[0].getAttribute('data-category-section');

            for (const section of sections) {
                const rect = section.getBoundingClientRect();
                if (rect.top <= triggerLine) {
                    activeSlug = section.getAttribute('data-category-section');
                } else {
                    break;
                }
            }

            if (activeSlug) {
                setActiveChip(activeSlug, true);
            }
        }

        window.addEventListener('scroll', function () {
            if (!ticking) {
                window.requestAnimationFrame(function () {
                    updateScrollspy();
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });

        window.addEventListener('scrollend', function () {
            if (isProgrammaticScroll) {
                if (scrollLockTimer) {
                    clearTimeout(scrollLockTimer);
                }
                isProgrammaticScroll = false;
            }
        }, { passive: true });

        // Handle initial selectedCategory from query string (slug or ID) or URL hash
        const initialSelected = @json($selectedCategory);
        if (initialSelected) {
            const matchedSection = sections.find((s) =>
                s.getAttribute('data-category-section') === String(initialSelected) ||
                s.getAttribute('data-category-id') === String(initialSelected)
            );
            if (matchedSection) {
                const matchedSlug = matchedSection.getAttribute('data-category-section');
                setTimeout(() => {
                    scrollToCategory(matchedSlug, true);
                }, 100);
            } else {
                updateScrollspy();
            }
        } else if (window.location.hash && window.location.hash.startsWith('#category-')) {
            const hashSlug = window.location.hash.replace('#category-', '');
            if (sections.some((s) => s.getAttribute('data-category-section') === hashSlug)) {
                setTimeout(() => {
                    scrollToCategory(hashSlug, true);
                }, 100);
            } else {
                updateScrollspy();
            }
        } else {
            updateScrollspy();
        }
    })();
</script>
@endsection
