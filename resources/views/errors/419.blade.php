<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>419 Sesi Kedaluwarsa — 774 HUB</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-[#FAF6EF] text-[#1F1A17] flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-2xl border border-[#E6DED2] p-8 text-center shadow-sm">
        <div class="mx-auto w-14 h-14 rounded-2xl bg-[#FEF3C7] text-[#92400E] flex items-center justify-center font-extrabold text-xl">
            419
        </div>
        <h1 class="mt-4 text-xl font-extrabold text-[#1F1A17]">Sesi Anda Telah Berakhir</h1>
        <p class="mt-2 text-sm text-[#6F6259]">
            Sesi keamanan telah kedaluwarsa setelah 30 menit tidak aktif (NFR-09). Silakan muat ulang halaman dan coba kembali.
        </p>
        <div class="mt-6">
            <a href="{{ url()->previous() ?: route('menu.index') }}"
               class="inline-flex items-center justify-center min-h-[44px] px-6 py-2.5 rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-sm font-bold hover:bg-[#2A1E17] transition">
                Muat Ulang Halaman
            </a>
        </div>
    </div>
</body>
</html>
