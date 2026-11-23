<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — 774 HUB' : '774 HUB — Coffee & Roastery' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-[#FAF6EF] text-[#1F1A17] antialiased flex flex-col pb-20 md:pb-8 print:pb-0 print:bg-white">
    @php
        $cartItems = (array) session('customer_cart', []);
        $cartCount = collect($cartItems)->sum('quantity');
        $tableLabel = session('qr_table_label');
        $currentOrderToken = session('current_order_token');
        $authUser = auth()->user();
        $isMember = $authUser && $authUser->hasRole('member');
        $isStaff = $authUser && $authUser->isStaff();
        $memberPoints = $isMember ? ($authUser->member?->points_balance ?? 0) : 0;
    @endphp

    @if(config('app.demo_mode') || config('app.payment_driver') === 'mock')
        <div class="print:hidden bg-[#3B2A20] text-[#FAF6EF] text-[11px] sm:text-xs px-3 py-1.5 text-center font-medium flex flex-wrap items-center justify-center gap-1.5 sm:gap-2 border-b border-[#A9552F]/40">
            @if(config('app.demo_mode'))
                <span class="px-2 py-0.5 rounded bg-[#A9552F] text-white font-bold uppercase tracking-wider text-[10px]">Data Demonstrasi</span>
            @endif
            @if(config('app.payment_driver') === 'mock')
                <span class="px-2 py-0.5 rounded bg-amber-600 text-white font-bold uppercase tracking-wider text-[10px]">SIMULASI QRIS & WA</span>
            @endif
            <span class="opacity-90 hidden sm:inline">Lingkungan Uji Coba 774 HUB</span>
        </div>
    @endif

    <header class="print:hidden sticky top-0 z-30 bg-[#FAF6EF]/95 backdrop-blur border-b border-[#E6DED2]">
        <div class="max-w-6xl mx-auto px-3.5 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-2 sm:gap-4">
            <a href="{{ route('menu.index') }}" class="flex items-center gap-2 sm:gap-2.5 focus:outline-none min-w-0 shrink-0">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#3B2A20] text-[#FAF6EF] flex items-center justify-center font-extrabold text-sm tracking-tight shrink-0">
                    774
                </div>
                <div class="min-w-0">
                    <div class="font-extrabold text-sm sm:text-base leading-tight text-[#1F1A17] truncate">774 HUB</div>
                    <div class="hidden sm:block text-[11px] text-[#6F6259] font-medium truncate">Coffee & Roastery</div>
                </div>
            </a>

            <!-- Tablet / Desktop Top Navigation -->
            <nav aria-label="Navigasi Utama" class="hidden md:flex items-center gap-1 lg:gap-2">
                <a href="{{ route('menu.index') }}"
                   class="min-h-[40px] px-3.5 py-2 rounded-xl text-xs font-bold inline-flex items-center transition {{ request()->routeIs('menu.*') ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'text-[#6F6259] hover:bg-[#F2ECE1] hover:text-[#1F1A17]' }}">
                    Menu
                </a>
                <a href="{{ route('promotions.public') }}"
                   class="min-h-[40px] px-3.5 py-2 rounded-xl text-xs font-bold inline-flex items-center transition {{ request()->routeIs('promotions.*') ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'text-[#6F6259] hover:bg-[#F2ECE1] hover:text-[#1F1A17]' }}">
                    Promo
                </a>
                @if($isMember)
                    <a href="{{ route('member.orders.index') }}"
                       class="min-h-[40px] px-3.5 py-2 rounded-xl text-xs font-bold inline-flex items-center transition {{ request()->routeIs('member.orders.*') || request()->routeIs('order.*') ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'text-[#6F6259] hover:bg-[#F2ECE1] hover:text-[#1F1A17]' }}">
                        Pesanan
                    </a>
                    <a href="{{ route('member.dashboard') }}"
                       class="min-h-[40px] px-3.5 py-2 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 transition {{ request()->routeIs('member.dashboard') || request()->routeIs('member.loyalty') || request()->routeIs('member.profile') ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'text-[#6F6259] hover:bg-[#F2ECE1] hover:text-[#1F1A17]' }}">
                        <span>Member</span>
                        <span class="px-1.5 py-0.5 rounded-full bg-[#A9552F] text-white text-[10px] font-extrabold tabular-nums">
                            {{ number_format($memberPoints, 0, ',', '.') }} Poin
                        </span>
                    </a>
                @elseif($isStaff)
                    <a href="{{ $authUser->homeRoute() }}"
                       class="min-h-[40px] px-3.5 py-2 rounded-xl text-xs font-bold inline-flex items-center text-[#A9552F] hover:bg-[#F2ECE1] transition">
                        Panel Staf
                    </a>
                @else
                    <a href="{{ $currentOrderToken ? route('order.track', ['public_token' => $currentOrderToken]) : route('cart.index') }}"
                       class="min-h-[40px] px-3.5 py-2 rounded-xl text-xs font-bold inline-flex items-center transition {{ request()->routeIs('order.*') || request()->routeIs('cart.*') ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'text-[#6F6259] hover:bg-[#F2ECE1] hover:text-[#1F1A17]' }}">
                        Pesanan
                    </a>
                    <a href="{{ route('login') }}"
                       class="min-h-[40px] px-3.5 py-2 rounded-xl text-xs font-bold inline-flex items-center transition {{ request()->routeIs('login') || request()->routeIs('register') ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'text-[#6F6259] hover:bg-[#F2ECE1] hover:text-[#1F1A17]' }}">
                        Masuk Member
                    </a>
                @endif
            </nav>

            <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                @if($isMember)
                    <a href="{{ route('member.profile') }}" title="Profil Member"
                       class="hidden sm:inline-flex items-center gap-1.5 min-h-[40px] px-2.5 py-1.5 rounded-xl bg-[#F2ECE1] border border-[#E6DED2] text-xs font-bold text-[#3B2A20] hover:bg-[#E6DED2] transition">
                        <svg class="w-3.5 h-3.5 text-[#A9552F] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="max-w-[75px] lg:max-w-[120px] truncate">{{ $authUser->name }}</span>
                    </a>
                @endif

                @if($tableLabel)
                    <span class="inline-flex items-center gap-1.5 min-h-[40px] px-2.5 sm:px-3 py-1.5 rounded-xl bg-[#F2ECE1] border border-[#E6DED2] text-[11px] sm:text-xs font-bold text-[#3B2A20] whitespace-nowrap">
                        <span class="w-2 h-2 rounded-full bg-[#1F7A4C] shrink-0"></span>
                        <span>{{ $tableLabel }}</span>
                    </span>
                @else
                    <a href="{{ route('home') }}" class="inline-flex items-center min-h-[40px] px-2.5 py-1.5 rounded-xl bg-amber-100 text-amber-900 text-[11px] sm:text-xs font-semibold whitespace-nowrap">
                        Pilih Meja
                    </a>
                @endif

                <a href="{{ route('cart.index') }}" aria-label="Keranjang Pesanan"
                   class="relative inline-flex items-center justify-center min-w-[42px] min-h-[42px] px-2.5 sm:px-3.5 rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-bold hover:bg-[#2A1E17] transition whitespace-nowrap">
                    <svg class="w-4 h-4 sm:hidden shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span class="hidden sm:inline">Keranjang</span>
                    @if($cartCount > 0)
                        <span class="ml-1 sm:ml-1.5 inline-flex items-center justify-center min-w-[20px] px-1.5 py-0.5 rounded-full bg-[#A9552F] text-white text-[10px] sm:text-[11px] font-extrabold tabular-nums">
                            {{ $cartCount }}
                        </span>
                    @endif
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-6xl w-full mx-auto px-3.5 sm:px-6 lg:px-8 py-4 sm:py-6 print:max-w-none print:p-0">
        @if(session('toast_success'))
            <div role="status" class="print:hidden mb-4 rounded-xl bg-[#DCFCE7] border border-[#86EFAC] text-[#166534] px-4 py-3 text-sm font-semibold">
                {{ session('toast_success') }}
            </div>
        @endif

        @if(session('toast_error'))
            <div role="alert" class="print:hidden mb-4 rounded-xl bg-[#FEE2E2] border border-[#FCA5A5] text-[#991B1B] px-4 py-3 text-sm font-semibold">
                {{ session('toast_error') }}
            </div>
        @endif

        @if(session('toast_warning'))
            <div role="alert" class="print:hidden mb-4 rounded-xl bg-[#FEF3C7] border border-[#FCD34D] text-[#92400E] px-4 py-3 text-sm font-semibold">
                {{ session('toast_warning') }}
            </div>
        @endif

        @if(session('toast_info'))
            <div role="status" class="print:hidden mb-4 rounded-xl bg-[#DBEAFE] border border-[#93C5FD] text-[#1E40AF] px-4 py-3 text-sm font-semibold">
                {{ session('toast_info') }}
            </div>
        @endif

        @if($errors->any())
            <div role="alert" class="print:hidden mb-4 rounded-xl bg-[#FEE2E2] border border-[#FCA5A5] text-[#991B1B] px-4 py-3 text-sm">
                <div class="font-bold mb-1">Periksa kembali isian berikut:</div>
                <ul class="list-disc list-inside space-y-0.5 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Mobile Bottom Tab Bar (Hidden on Tablet Landscape & Desktop md+) -->
    <nav aria-label="Navigasi Pelanggan" class="md:hidden print:hidden fixed bottom-0 inset-x-0 z-30 bg-white/95 backdrop-blur border-t border-[#E6DED2]">
        <div class="max-w-2xl mx-auto grid grid-cols-4 h-16">
            <a href="{{ route('menu.index') }}" class="flex flex-col items-center justify-center gap-0.5 px-1 text-[11px] text-center font-semibold {{ request()->routeIs('menu.*') ? 'text-[#A9552F] font-bold' : 'text-[#6F6259]' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <span class="truncate max-w-full">Menu</span>
            </a>
            <a href="{{ route('promotions.public') }}" class="flex flex-col items-center justify-center gap-0.5 px-1 text-[11px] text-center font-semibold {{ request()->routeIs('promotions.*') ? 'text-[#A9552F] font-bold' : 'text-[#6F6259]' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                <span class="truncate max-w-full">Promo</span>
            </a>
            @if($isMember)
                <a href="{{ route('member.orders.index') }}" class="flex flex-col items-center justify-center gap-0.5 px-1 text-[11px] text-center font-semibold {{ request()->routeIs('member.orders.*') || request()->routeIs('order.*') ? 'text-[#A9552F] font-bold' : 'text-[#6F6259]' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span class="truncate max-w-full">Pesanan</span>
                </a>
                <a href="{{ route('member.dashboard') }}" class="flex flex-col items-center justify-center gap-0.5 px-1 text-[11px] text-center font-semibold {{ request()->routeIs('member.dashboard') || request()->routeIs('member.loyalty') || request()->routeIs('member.profile') ? 'text-[#A9552F] font-bold' : 'text-[#6F6259]' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="truncate max-w-full">{{ number_format($memberPoints, 0, ',', '.') }} Poin</span>
                </a>
            @elseif($isStaff)
                <a href="{{ $currentOrderToken ? route('order.track', ['public_token' => $currentOrderToken]) : route('cart.index') }}" class="flex flex-col items-center justify-center gap-0.5 px-1 text-[11px] text-center font-semibold {{ request()->routeIs('order.*') || request()->routeIs('cart.*') ? 'text-[#A9552F] font-bold' : 'text-[#6F6259]' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span class="truncate max-w-full">Pesanan</span>
                </a>
                <a href="{{ $authUser->homeRoute() }}" class="flex flex-col items-center justify-center gap-0.5 px-1 text-[11px] text-center font-semibold text-[#3B2A20]">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span class="truncate max-w-full">Panel Staf</span>
                </a>
            @else
                <a href="{{ $currentOrderToken ? route('order.track', ['public_token' => $currentOrderToken]) : route('cart.index') }}" class="flex flex-col items-center justify-center gap-0.5 px-1 text-[11px] text-center font-semibold {{ request()->routeIs('order.*') || request()->routeIs('cart.*') ? 'text-[#A9552F] font-bold' : 'text-[#6F6259]' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span class="truncate max-w-full">Pesanan</span>
                </a>
                <a href="{{ route('login') }}" class="flex flex-col items-center justify-center gap-0.5 px-1 text-[11px] text-center font-semibold {{ request()->routeIs('login') || request()->routeIs('register') ? 'text-[#A9552F] font-bold' : 'text-[#6F6259]' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    <span class="truncate max-w-full">Masuk</span>
                </a>
            @endif
        </div>
    </nav>
</body>
</html>
