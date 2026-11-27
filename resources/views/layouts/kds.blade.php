<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KDS Dapur & Barista — 774 HUB</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=3">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=3">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=3">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-[#1F1A17] text-[#FAF6EF] antialiased flex flex-col">
    <header class="sticky top-0 z-30 bg-[#2C221D]/95 backdrop-blur border-b border-white/10 px-3.5 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <img src="{{ asset('images/logo-774.png') }}" alt="774 HUB" class="w-10 h-10 rounded-full object-cover shrink-0 ring-1 ring-white/20">
            <div class="min-w-0">
                <h1 class="font-extrabold text-base sm:text-lg leading-tight text-white truncate">Kitchen Display System (KDS)</h1>
                <p class="text-[11px] sm:text-xs text-[#FAF6EF]/70 truncate">774 HUB — Antrean Pesanan Dibayar (Real-Time)</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 sm:gap-2.5 w-full lg:w-auto">
            <a href="{{ route('kds.index') }}" class="min-h-[40px] inline-flex items-center justify-center px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ request()->routeIs('kds.index') ? 'bg-[#A9552F] text-white' : 'bg-white/10 text-[#FAF6EF] hover:bg-white/20' }}">
                Papan Antrean KDS
            </a>
            <a href="{{ route('kds.history') }}" class="min-h-[40px] inline-flex items-center justify-center px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ request()->routeIs('kds.history') ? 'bg-[#A9552F] text-white' : 'bg-white/10 text-[#FAF6EF] hover:bg-white/20' }}">
                Riwayat & Stok Menu
            </a>
            @if(request()->routeIs('kds.index'))
                <div id="kds-live-indicator" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-950 border border-emerald-600 text-emerald-300 text-xs font-bold whitespace-nowrap">
                    <span id="kds-live-dot" class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
                    <span id="kds-live-text">Sinkron Aktif</span>
                </div>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="ml-auto lg:ml-0">
                @csrf
                <button type="submit" class="min-h-[40px] px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-semibold text-white whitespace-nowrap transition">
                    Keluar ({{ \Illuminate\Support\Str::limit(auth()->user()?->name ?? 'Staf', 14) }})
                </button>
            </form>
        </div>
    </header>

    <main class="flex-1 p-3 sm:p-5 lg:p-6 overflow-x-hidden">
        @if(session('toast_success'))
            <div role="status" class="mb-4 rounded-xl bg-emerald-950 border border-emerald-500 text-emerald-200 px-4 py-3 text-sm font-semibold">
                {{ session('toast_success') }}
            </div>
        @endif

        @if(session('toast_error'))
            <div role="alert" class="mb-4 rounded-xl bg-red-950 border border-red-500 text-red-200 px-4 py-3 text-sm font-semibold">
                {{ session('toast_error') }}
            </div>
        @endif

        @if(session('toast_info'))
            <div role="status" class="mb-4 rounded-xl bg-blue-950 border border-blue-500 text-blue-200 px-4 py-3 text-sm font-semibold">
                {{ session('toast_info') }}
            </div>
        @endif

        @if($errors->any())
            <div role="alert" class="mb-4 rounded-xl bg-red-950 border border-red-500 text-red-200 px-4 py-3 text-sm">
                <div class="font-bold mb-1">Periksa kembali:</div>
                <ul class="list-disc list-inside space-y-0.5 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
