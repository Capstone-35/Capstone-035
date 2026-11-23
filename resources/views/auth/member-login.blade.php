@extends('layouts.customer', ['title' => 'Masuk Member'])

@section('content')
<div class="max-w-md mx-auto bg-white rounded-2xl border border-[#E6DED2] p-5 sm:p-6 shadow-sm">
    <div class="mb-5">
        <span class="inline-block px-2.5 py-1 rounded-full bg-[#F2ECE1] text-[#3B2A20] text-xs font-bold mb-2">Program Loyalitas 774 HUB</span>
        <h1 class="text-xl font-extrabold text-[#1F1A17]">Masuk sebagai Member</h1>
        <p class="text-sm text-[#6F6259] mt-1">
            Dapatkan poin loyalti, nikmati promo berbasis kebiasaan pesanan (RFM), dan pesan ulang menu favorit Anda.
        </p>
    </div>

    <form method="POST" action="{{ route('login.store') }}" onsubmit="this.querySelector('button[type=submit]').disabled=true;" class="space-y-4">
        @csrf
        <div>
            <label for="contact" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1.5">
                Email atau Nomor WhatsApp
            </label>
            <input id="contact" name="contact" type="text" value="{{ old('contact') }}" required autofocus autocomplete="username" maxlength="120"
                   placeholder="contoh: member1@example.test atau 0812..."
                   class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm focus:outline-none focus:ring-2 focus:ring-[#A9552F]">
        </div>

        <div>
            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1.5">
                Kata Sandi
            </label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   placeholder="Masukkan kata sandi"
                   class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm focus:outline-none focus:ring-2 focus:ring-[#A9552F]">
        </div>

        <button type="submit" class="w-full min-h-[46px] rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-sm font-bold hover:bg-[#2A1E17] transition">
            Masuk Member
        </button>
    </form>

    <div class="mt-5 pt-4 border-t border-[#E6DED2] flex flex-col sm:flex-row items-center justify-between gap-2 text-xs">
        <span class="text-[#6F6259]">Belum punya akun Member?</span>
        <a href="{{ route('register') }}" class="min-h-[40px] inline-flex items-center font-bold text-[#A9552F] hover:underline">
            Daftar Member Baru →
        </a>
    </div>

    @if(config('app.demo_mode'))
        <div class="mt-4 p-3.5 rounded-xl bg-[#F2ECE1] border border-[#E6DED2] text-xs text-[#3B2A20] break-words">
            <div class="font-bold mb-1">Akun Demo Member (Data Demonstrasi):</div>
            <div>Email: <code class="font-mono font-bold">member1@example.test</code> s/d <code class="font-mono font-bold">member40@example.test</code></div>
            <div>Sandi: <code class="font-mono font-bold">Demo#774hub</code></div>
        </div>
    @endif

    <div class="mt-3 text-center">
        <a href="{{ route('staff.login') }}" class="min-h-[40px] inline-flex items-center justify-center text-xs text-[#6F6259] hover:text-[#3B2A20] underline">
            Portal Login Staf Operasional (Kasir / Kitchen / Manager / Owner)
        </a>
    </div>
</div>
@endsection
