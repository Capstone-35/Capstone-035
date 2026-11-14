<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — 774 HUB Staff' : '774 HUB — Sistem POS & Manajemen' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-[#FAF6EF] text-[#1F1A17] antialiased">
    @php
        $user = auth()->user();
        $primaryRole = $user?->getRoleNames()->first() ?? 'kasir';
        $navItems = config("navigation.{$primaryRole}", []);
    @endphp

    <div class="min-h-screen flex flex-col lg:flex-row">
        <!-- Sidebar Navigation -->
        <aside class="w-full lg:w-64 bg-[#3B2A20] text-[#FAF6EF] flex flex-col shrink-0 border-b lg:border-b-0 lg:border-r border-[#2A1E17]">
            <div class="p-4 lg:p-5 flex items-center justify-between border-b border-white/10">
                <a href="{{ $user ? $user->homeRoute() : '/' }}" class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#A9552F] text-white flex items-center justify-center font-extrabold text-sm">
                        774
                    </div>
                    <div>
                        <div class="font-extrabold text-base leading-tight">774 HUB</div>
                        <div class="text-xs text-[#FAF6EF]/70 uppercase tracking-wider font-semibold">{{ strtoupper($primaryRole) }} PANEL</div>
                    </div>
                </a>
            </div>

            <nav aria-label="Menu Utama Staf" class="flex-1 px-3 py-4 flex lg:flex-col gap-1 overflow-x-auto lg:overflow-x-visible">
                @foreach($navItems as $item)
                    @php
                        $patterns = explode('|', $item['active'] ?? $item['route']);
                        $isActive = false;
                        foreach ($patterns as $pattern) {
                            if (request()->routeIs(trim($pattern))) {
                                $isActive = true;
                                break;
                            }
                        }
                    @endphp
                    @if(empty($item['permission']) || ($user && $user->can($item['permission'])))
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-sm font-semibold whitespace-nowrap transition {{ $isActive ? 'bg-[#A9552F] text-white shadow-sm' : 'text-[#FAF6EF]/80 hover:bg-white/10 hover:text-white' }}">
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endif
                @endforeach

                @if($primaryRole === 'manager' && !app()->environment('production') && config('app.payment_driver') === 'mock')
                    <a href="{{ route('sandbox.payments.index') }}"
                       class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-sm font-semibold whitespace-nowrap transition {{ request()->routeIs('sandbox.payments.*') ? 'bg-amber-600 text-white' : 'text-amber-300 hover:bg-white/10' }}">
                        <span>Simulator QRIS</span>
                    </a>
                @endif
            </nav>

            <div class="p-4 border-t border-white/10 flex items-center justify-between gap-2 text-xs">
                <div class="truncate">
                    <div class="font-bold text-white truncate">{{ $user?->name }}</div>
                    <div class="text-[#FAF6EF]/70 truncate">{{ $user?->email }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white font-semibold transition">
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <header class="bg-white border-b border-[#E6DED2] px-4 lg:px-8 py-3.5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <h1 class="text-lg lg:text-xl font-extrabold text-[#1F1A17]">
                        @yield('heading', $title ?? 'Dashboard Operasional')
                    </h1>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs">
                    @if(config('app.demo_mode'))
                        <span class="px-2.5 py-1 rounded-full bg-[#F2ECE1] border border-[#E6DED2] text-[#3B2A20] font-bold">
                            Data Demonstrasi
                        </span>
                    @endif
                    @if(config('app.payment_driver') === 'mock')
                        <span class="px-2.5 py-1 rounded-full bg-amber-100 border border-amber-300 text-amber-900 font-bold">
                            SIMULASI QRIS & WA
                        </span>
                    @endif
                    <span class="px-2.5 py-1 rounded-full bg-[#3B2A20] text-[#FAF6EF] font-semibold uppercase">
                        {{ $primaryRole }}
                    </span>
                </div>
            </header>

            <main class="flex-1 p-4 lg:p-8 max-w-7xl w-full mx-auto">
                @if(session('toast_success'))
                    <div role="status" class="mb-5 rounded-xl bg-[#DCFCE7] border border-[#86EFAC] text-[#166534] px-4 py-3 text-sm font-semibold">
                        {{ session('toast_success') }}
                    </div>
                @endif

                @if(session('toast_error'))
                    <div role="alert" class="mb-5 rounded-xl bg-[#FEE2E2] border border-[#FCA5A5] text-[#991B1B] px-4 py-3 text-sm font-semibold">
                        {{ session('toast_error') }}
                    </div>
                @endif

                @if(session('toast_info'))
                    <div role="status" class="mb-5 rounded-xl bg-[#DBEAFE] border border-[#93C5FD] text-[#1E40AF] px-4 py-3 text-sm font-semibold">
                        {{ session('toast_info') }}
                    </div>
                @endif

                @if($errors->any())
                    <div role="alert" class="mb-5 rounded-xl bg-[#FEE2E2] border border-[#FCA5A5] text-[#991B1B] px-4 py-3 text-sm">
                        <div class="font-bold mb-1">Terdapat kesalahan validasi:</div>
                        <ul class="list-disc list-inside space-y-0.5 text-xs">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
