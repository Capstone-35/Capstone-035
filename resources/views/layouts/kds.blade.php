<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KDS Dapur & Barista — 774 HUB</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-[#1F1A17] text-[#FAF6EF] antialiased flex flex-col">
    <header class="bg-[#2C221D] border-b border-white/10 px-6 py-3.5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-[#A9552F] text-white flex items-center justify-center font-extrabold text-sm">
                774
            </div>
            <div>
                <h1 class="font-extrabold text-lg leading-tight text-white">Kitchen Display System (KDS)</h1>
                <p class="text-xs text-[#FAF6EF]/70">774 HUB — Antrean Pesanan Dibayar (Real-Time)</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('kds.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('kds.index') ? 'bg-[#A9552F] text-white' : 'bg-white/10 text-[#FAF6EF] hover:bg-white/20' }}">
                Papan Antrean KDS
            </a>
            <a href="{{ route('kds.history') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('kds.history') ? 'bg-[#A9552F] text-white' : 'bg-white/10 text-[#FAF6EF] hover:bg-white/20' }}">
                Riwayat Selesai & Ketersediaan Menu
            </a>
            <div id="kds-live-indicator" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-950 border border-emerald-600 text-emerald-300 text-xs font-bold">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Sinkron Aktif</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-semibold text-white">
                    Keluar ({{ auth()->user()?->name }})
                </button>
            </form>
        </div>
    </header>

    <main class="flex-1 p-5 overflow-x-hidden">
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

        @if($errors->any())
            <div role="alert" class="mb-4 rounded-xl bg-red-950 border border-red-500 text-red-200 px-4 py-3 text-sm font-semibold">
                {{ $errors->first() }}
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
