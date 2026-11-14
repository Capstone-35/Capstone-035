<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — 774 HUB' : '774 HUB — Coffee & Roastery' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-[#FAF6EF] text-[#1F1A17] antialiased flex flex-col pb-20">
    @php
        $cartItems = (array) session('customer_cart', []);
        $cartCount = collect($cartItems)->sum('quantity');
        $tableLabel = session('qr_table_label');
        $currentOrderToken = session('current_order_token');
    @endphp

    @if(config('app.demo_mode') || config('app.payment_driver') === 'mock')
        <div class="bg-[#3B2A20] text-[#FAF6EF] text-xs px-3 py-1.5 text-center font-medium flex flex-wrap items-center justify-center gap-2 border-b border-[#A9552F]/40">
            @if(config('app.demo_mode'))
                <span class="px-2 py-0.5 rounded bg-[#A9552F] text-white font-bold uppercase tracking-wider text-[10px]">Data Demonstrasi</span>
            @endif
            @if(config('app.payment_driver') === 'mock')
                <span class="px-2 py-0.5 rounded bg-amber-600 text-white font-bold uppercase tracking-wider text-[10px]">SIMULASI QRIS & WA</span>
            @endif
            <span class="opacity-90">Lingkungan Uji Coba 774 HUB</span>
        </div>
    @endif

    <header class="sticky top-0 z-30 bg-[#FAF6EF]/95 backdrop-blur border-b border-[#E6DED2]">
        <div class="max-w-2xl mx-auto px-4 h-16 flex items-center justify-between gap-2">
            <a href="{{ route('menu.index') }}" class="flex items-center gap-2.5 focus:outline-none">
                <div class="w-10 h-10 rounded-xl bg-[#3B2A20] text-[#FAF6EF] flex items-center justify-center font-extrabold text-sm tracking-tight">
                    774
                </div>
                <div>
                    <div class="font-extrabold text-base leading-tight text-[#1F1A17]">774 HUB</div>
                    <div class="text-[11px] text-[#6F6259] font-medium">Coffee & Roastery</div>
                </div>
            </a>

            <div class="flex items-center gap-2">
                @if(auth()->check() && auth()->user()->hasRole('member'))
                    <a href="{{ route('member.profile') }}" title="Profil Member" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-[#F2ECE1] border border-[#E6DED2] text-xs font-bold text-[#3B2A20] hover:bg-[#E6DED2] transition">
                        <svg class="w-3.5 h-3.5 text-[#A9552F]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="max-w-[70px] sm:max-w-[120px] truncate">{{ auth()->user()->name }}</span>
                    </a>
                @endif

                @if($tableLabel)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#F2ECE1] border border-[#E6DED2] text-xs font-bold text-[#3B2A20]">
                        <span class="w-2 h-2 rounded-full bg-[#1F7A4C]"></span>
                        {{ $tableLabel }}
                    </span>
                @else
                    <a href="{{ route('home') }}" class="inline-flex items-center px-2.5 py-1.5 rounded-xl bg-amber-100 text-amber-900 text-xs font-semibold">
                        Pilih Meja
                    </a>
                @endif

                <a href="{{ route('cart.index') }}" aria-label="Keranjang Pesanan" class="relative inline-flex items-center justify-center min-w-[44px] min-h-[44px] px-3 rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-bold hover:bg-[#2A1E17] transition">
                    <span>Keranjang</span>
                    @if($cartCount > 0)
                        <span class="ml-1.5 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full bg-[#A9552F] text-white text-[11px] font-extrabold tabular-nums">
                            {{ $cartCount }}
                        </span>
                    @endif
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-2xl w-full mx-auto px-4 py-5">
        @if(session('toast_success'))
            <div role="status" class="mb-4 rounded-xl bg-[#DCFCE7] border border-[#86EFAC] text-[#166534] px-4 py-3 text-sm font-semibold">
                {{ session('toast_success') }}
            </div>
        @endif

        @if(session('toast_error'))
            <div role="alert" class="mb-4 rounded-xl bg-[#FEE2E2] border border-[#FCA5A5] text-[#991B1B] px-4 py-3 text-sm font-semibold">
                {{ session('toast_error') }}
            </div>
        @endif

        @if(session('toast_info'))
            <div role="status" class="mb-4 rounded-xl bg-[#DBEAFE] border border-[#93C5FD] text-[#1E40AF] px-4 py-3 text-sm font-semibold">
                {{ session('toast_info') }}
            </div>
        @endif

        @if($errors->any())
            <div role="alert" class="mb-4 rounded-xl bg-[#FEE2E2] border border-[#FCA5A5] text-[#991B1B] px-4 py-3 text-sm">
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

    <nav aria-label="Navigasi Pelanggan" class="fixed bottom-0 inset-x-0 z-30 bg-white/95 backdrop-blur border-t border-[#E6DED2]">
        <div class="max-w-2xl mx-auto grid grid-cols-4 h-16">
            <a href="{{ route('menu.index') }}" class="flex flex-col items-center justify-center gap-0.5 text-xs font-semibold {{ request()->routeIs('menu.*') ? 'text-[#A9552F] font-bold' : 'text-[#6F6259]' }}">
                <span>Menu</span>
            </a>
            <a href="{{ route('promotions.public') }}" class="flex flex-col items-center justify-center gap-0.5 text-xs font-semibold {{ request()->routeIs('promotions.*') ? 'text-[#A9552F] font-bold' : 'text-[#6F6259]' }}">
                <span>Promo</span>
            </a>
            @if(auth()->check() && auth()->user()->hasRole('member'))
                <a href="{{ route('member.orders.index') }}" class="flex flex-col items-center justify-center gap-0.5 text-xs font-semibold {{ request()->routeIs('member.orders.*') || request()->routeIs('order.*') ? 'text-[#A9552F] font-bold' : 'text-[#6F6259]' }}">
                    <span>Pesanan</span>
                </a>
                <a href="{{ route('member.dashboard') }}" class="flex flex-col items-center justify-center gap-0.5 text-xs font-semibold {{ request()->routeIs('member.dashboard') || request()->routeIs('member.loyalty') || request()->routeIs('member.profile') ? 'text-[#A9552F] font-bold' : 'text-[#6F6259]' }}">
                    <span>Member ({{ number_format(auth()->user()->member?->points_balance ?? 0, 0, ',', '.') }} Poin)</span>
                </a>
            @else
                <a href="{{ $currentOrderToken ? route('order.track', ['public_token' => $currentOrderToken]) : route('cart.index') }}" class="flex flex-col items-center justify-center gap-0.5 text-xs font-semibold {{ request()->routeIs('order.*') || request()->routeIs('cart.*') ? 'text-[#A9552F] font-bold' : 'text-[#6F6259]' }}">
                    <span>Pesanan</span>
                </a>
                <a href="{{ route('login') }}" class="flex flex-col items-center justify-center gap-0.5 text-xs font-semibold {{ request()->routeIs('login') || request()->routeIs('register') ? 'text-[#A9552F] font-bold' : 'text-[#6F6259]' }}">
                    <span>Masuk Member</span>
                </a>
            @endif
        </div>
    </nav>
</body>
</html>
