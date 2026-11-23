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

            @php
                $allNavItems = $navItems;
                if ($primaryRole === 'manager' && !app()->environment('production') && config('app.payment_driver') === 'mock') {
                    $allNavItems[] = [
                        'label' => 'Simulator QRIS',
                        'route' => 'sandbox.payments.index',
                        'active' => 'sandbox.payments.*',
                        'icon' => 'qr-code',
                        'permission' => null,
                        'group' => 'Pengaturan',
                        'is_sandbox' => true,
                    ];
                }

                $visibleNavItems = [];
                foreach ($allNavItems as $item) {
                    if (!empty($item['permission']) && (!$user || !$user->can($item['permission']))) {
                        continue;
                    }
                    $patterns = explode('|', $item['active'] ?? $item['route']);
                    $isActive = false;
                    foreach ($patterns as $pattern) {
                        if (request()->routeIs(trim($pattern))) {
                            $isActive = true;
                            break;
                        }
                    }
                    $item['is_active'] = $isActive;
                    $visibleNavItems[] = $item;
                }

                $hasGroups = collect($visibleNavItems)->contains(fn ($i) => !empty($i['group']));
                $groupedNavItems = $hasGroups
                    ? collect($visibleNavItems)->groupBy(fn ($i) => $i['group'] ?? 'Lainnya')
                    : collect();
            @endphp

            @if($hasGroups)
                <nav aria-label="Menu Utama Staf" class="flex-1 px-3 py-4 flex flex-col gap-3 overflow-y-auto">
                    @foreach($groupedNavItems as $groupName => $groupItems)
                        @php
                            $groupSlug = \Illuminate\Support\Str::slug($groupName);
                            $isGroupActive = $groupItems->contains('is_active', true);
                        @endphp

                        @if($groupName === 'Utama')
                            <div class="space-y-1">
                                @foreach($groupItems as $item)
                                    <a href="{{ route($item['route']) }}"
                                       class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-sm font-bold whitespace-nowrap transition {{ $item['is_active'] ? 'bg-[#A9552F] text-white shadow-sm' : 'text-[#FAF6EF]/90 hover:bg-white/10 hover:text-white' }}">
                                        <svg class="w-4 h-4 shrink-0 {{ $item['is_active'] ? 'text-white' : 'text-[#A9552F]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        <span>{{ $item['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-xl bg-black/15 border border-white/5 overflow-hidden" data-nav-group="{{ $groupSlug }}" data-group-active="{{ $isGroupActive ? 'true' : 'false' }}">
                                <button type="button"
                                        onclick="toggleNavGroup('{{ $groupSlug }}')"
                                        aria-expanded="{{ $isGroupActive ? 'true' : 'false' }}"
                                        id="nav-group-btn-{{ $groupSlug }}"
                                        class="w-full px-3.5 py-2.5 flex items-center justify-between gap-2 text-left text-xs font-extrabold uppercase tracking-wider transition select-none cursor-pointer {{ $isGroupActive ? 'text-amber-300 bg-white/10' : 'text-[#FAF6EF]/80 hover:text-white hover:bg-white/5' }}">
                                    <span class="flex items-center gap-2.5 truncate">
                                        <span class="w-2 h-2 rounded-full {{ $isGroupActive ? 'bg-amber-400' : 'bg-[#A9552F]' }}"></span>
                                        <span>{{ $groupName }}</span>
                                    </span>
                                    <svg id="nav-group-icon-{{ $groupSlug }}"
                                         class="w-4 h-4 shrink-0 transition-transform duration-200 {{ $isGroupActive ? 'rotate-180' : '' }}"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div id="nav-group-items-{{ $groupSlug }}" class="{{ $isGroupActive ? '' : 'hidden' }} px-2 pb-2 pt-1 space-y-1 border-t border-white/5">
                                    @foreach($groupItems as $item)
                                        @if(!empty($item['is_sandbox']))
                                            <a href="{{ route($item['route']) }}"
                                               class="flex items-center gap-2.5 pl-4 pr-3 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition {{ $item['is_active'] ? 'bg-amber-600 text-white shadow-sm' : 'text-amber-300 hover:bg-white/10' }}">
                                                <span class="w-1.5 h-1.5 rounded-full bg-current opacity-75"></span>
                                                <span>{{ $item['label'] }}</span>
                                            </a>
                                        @else
                                            <a href="{{ route($item['route']) }}"
                                               class="flex items-center gap-2.5 pl-4 pr-3 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition {{ $item['is_active'] ? 'bg-[#A9552F] text-white shadow-sm' : 'text-[#FAF6EF]/80 hover:bg-white/10 hover:text-white' }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $item['is_active'] ? 'bg-white' : 'bg-white/30' }}"></span>
                                                <span>{{ $item['label'] }}</span>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </nav>
            @else
                <nav aria-label="Menu Utama Staf" class="flex-1 px-3 py-4 flex lg:flex-col gap-1 overflow-x-auto lg:overflow-x-visible">
                    @foreach($visibleNavItems as $item)
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-sm font-semibold whitespace-nowrap transition {{ $item['is_active'] ? 'bg-[#A9552F] text-white shadow-sm' : 'text-[#FAF6EF]/80 hover:bg-white/10 hover:text-white' }}">
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
            @endif

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

    <script>
        function toggleNavGroup(slug) {
            const itemsEl = document.getElementById('nav-group-items-' + slug);
            const iconEl = document.getElementById('nav-group-icon-' + slug);
            const btnEl = document.getElementById('nav-group-btn-' + slug);
            if (!itemsEl) return;

            const isHidden = itemsEl.classList.contains('hidden');
            if (isHidden) {
                itemsEl.classList.remove('hidden');
                iconEl?.classList.add('rotate-180');
                btnEl?.setAttribute('aria-expanded', 'true');
            } else {
                itemsEl.classList.add('hidden');
                iconEl?.classList.remove('rotate-180');
                btnEl?.setAttribute('aria-expanded', 'false');
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
