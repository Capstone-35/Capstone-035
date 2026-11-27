<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 Akses Ditolak — 774 HUB</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=3">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=3">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-[#FAF6EF] text-[#1F1A17] flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-2xl border border-[#E6DED2] p-8 text-center shadow-sm">
        <div class="mx-auto w-14 h-14 rounded-2xl bg-[#FEE2E2] text-[#991B1B] flex items-center justify-center font-extrabold text-xl">
            403
        </div>
        <h1 class="mt-4 text-xl font-extrabold text-[#1F1A17]">Anda tidak memiliki akses ke halaman ini</h1>
        @php
            $rawMsg = isset($exception) ? trim((string) $exception->getMessage()) : '';
            $isEnglishRoleMsg = str_starts_with($rawMsg, 'User does not have');
            $displayMsg = ($rawMsg !== '' && ! $isEnglishRoleMsg)
                ? $rawMsg
                : 'Peran akun Anda tidak memiliki izin untuk membuka modul atau tindakan yang diminta.';
        @endphp
        <p class="mt-2 text-sm text-[#6F6259]">
            {{ $displayMsg }}
        </p>
        <div class="mt-6">
            <a href="{{ auth()->check() ? auth()->user()->homeRoute() : route('menu.index') }}"
               class="inline-flex items-center justify-center min-h-[44px] px-6 py-2.5 rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-sm font-bold hover:bg-[#2A1E17] transition">
                Kembali ke Beranda
            </a>
        </div>
    </div>
</body>
</html>
