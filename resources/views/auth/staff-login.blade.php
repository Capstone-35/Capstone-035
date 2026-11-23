<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login Staf Operasional — 774 HUB</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-[#3B2A20] text-[#1F1A17] flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-[#FAF6EF] rounded-2xl border border-[#E6DED2] p-6 sm:p-8 shadow-xl">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-[#3B2A20] text-[#FAF6EF] flex items-center justify-center font-extrabold text-base">
                774
            </div>
            <div>
                <h1 class="text-lg font-extrabold text-[#1F1A17]">Portal Staf 774 HUB</h1>
                <p class="text-xs text-[#6F6259]">Kasir POS • Kitchen KDS • Manager • Owner</p>
            </div>
        </div>

        @if(session('toast_success'))
            <div class="mb-4 rounded-xl bg-[#DCFCE7] border border-[#86EFAC] text-[#166534] px-4 py-3 text-xs font-semibold">
                {{ session('toast_success') }}
            </div>
        @endif

        @if(session('toast_warning'))
            <div class="mb-4 rounded-xl bg-[#FEF3C7] border border-[#FCD34D] text-[#92400E] px-4 py-3 text-xs font-semibold">
                {{ session('toast_warning') }}
            </div>
        @endif

        @if(session('toast_error'))
            <div class="mb-4 rounded-xl bg-[#FEE2E2] border border-[#FCA5A5] text-[#991B1B] px-4 py-3 text-xs font-semibold">
                {{ session('toast_error') }}
            </div>
        @endif

        @if(session('toast_info'))
            <div class="mb-4 rounded-xl bg-[#DBEAFE] border border-[#93C5FD] text-[#1E40AF] px-4 py-3 text-xs font-semibold">
                {{ session('toast_info') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 rounded-xl bg-[#FEE2E2] border border-[#FCA5A5] text-[#991B1B] px-4 py-3 text-xs font-semibold">
                @foreach($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('staff.login.store') }}" onsubmit="this.querySelector('button[type=submit]').disabled=true;" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1.5">
                    Email Staf
                </label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       placeholder="kasir@774hub.test"
                       class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#A9552F]">
            </div>

            <div>
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1.5">
                    Kata Sandi
                </label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       placeholder="Masukkan kata sandi"
                       class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#A9552F]">
            </div>

            <button type="submit" class="w-full min-h-[46px] rounded-xl bg-[#A9552F] text-white text-sm font-bold hover:bg-[#8E4423] transition">
                Masuk Portal Staf
            </button>
        </form>

        @if(config('app.demo_mode'))
            <div class="mt-6 pt-5 border-t border-[#E6DED2]">
                <div class="text-xs font-bold text-[#3B2A20] mb-2">Kredensial Demo Cepat (Klik untuk isi otomatis):</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    <button type="button" onclick="fillStaff('kasir@774hub.test')" class="min-h-[44px] px-3 py-2 rounded-xl bg-white border border-[#D6CBBC] hover:border-[#A9552F] text-left min-w-0">
                        <div class="font-bold text-[#3B2A20] truncate">Kasir (POS)</div>
                        <div class="text-[11px] text-[#6F6259] truncate">kasir@774hub.test</div>
                    </button>
                    <button type="button" onclick="fillStaff('kitchen@774hub.test')" class="min-h-[44px] px-3 py-2 rounded-xl bg-white border border-[#D6CBBC] hover:border-[#A9552F] text-left min-w-0">
                        <div class="font-bold text-[#3B2A20] truncate">Kitchen (KDS)</div>
                        <div class="text-[11px] text-[#6F6259] truncate">kitchen@774hub.test</div>
                    </button>
                    <button type="button" onclick="fillStaff('manager@774hub.test')" class="min-h-[44px] px-3 py-2 rounded-xl bg-white border border-[#D6CBBC] hover:border-[#A9552F] text-left min-w-0">
                        <div class="font-bold text-[#3B2A20] truncate">Manager</div>
                        <div class="text-[11px] text-[#6F6259] truncate">manager@774hub.test</div>
                    </button>
                    <button type="button" onclick="fillStaff('owner@774hub.test')" class="min-h-[44px] px-3 py-2 rounded-xl bg-white border border-[#D6CBBC] hover:border-[#A9552F] text-left min-w-0">
                        <div class="font-bold text-[#3B2A20] truncate">Owner</div>
                        <div class="text-[11px] text-[#6F6259] truncate">owner@774hub.test</div>
                    </button>
                </div>
            </div>
            <script>
                function fillStaff(email) {
                    document.getElementById('email').value = email;
                    document.getElementById('password').value = 'Demo#774hub';
                }
            </script>
        @endif

        <div class="mt-4 text-center">
            <a href="{{ route('menu.index') }}" class="min-h-[40px] inline-flex items-center justify-center text-xs text-[#6F6259] hover:text-[#3B2A20] underline">
                ← Kembali ke Halaman Pemesanan Pelanggan
            </a>
        </div>
    </div>
</body>
</html>
