@extends('layouts.customer', ['title' => 'Daftar Member Baru'])

@section('content')
<div class="bg-white rounded-2xl border border-[#E6DED2] p-6 shadow-sm">
    <div class="mb-5">
        <span class="inline-block px-2.5 py-1 rounded-full bg-[#F2ECE1] text-[#3B2A20] text-xs font-bold mb-2">Welcome Promo Tersedia</span>
        <h1 class="text-xl font-extrabold text-[#1F1A17]">Daftar Member 774 HUB</h1>
        <p class="text-sm text-[#6F6259] mt-1">
            Daftar gratis dan dapatkan akses ke <strong>Welcome / Next Visit Promo</strong>, pengumpulan poin, serta penukaran voucher reward.
        </p>
    </div>

    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="name" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1.5">
                Nama Lengkap
            </label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required
                   placeholder="Nama lengkap Anda"
                   class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm focus:outline-none focus:ring-2 focus:ring-[#A9552F]">
        </div>

        <div>
            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1.5">
                Alamat Email (Untuk Invoice Digital)
            </label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                   placeholder="nama@email.com"
                   class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm focus:outline-none focus:ring-2 focus:ring-[#A9552F]">
        </div>

        <div>
            <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1.5">
                Nomor WhatsApp / Telepon
            </label>
            <input id="phone" name="phone" type="text" value="{{ old('phone') }}" required
                   placeholder="0812xxxxxxxx"
                   class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm focus:outline-none focus:ring-2 focus:ring-[#A9552F]">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1.5">
                    Kata Sandi (Min. 8 Karakter)
                </label>
                <input id="password" name="password" type="password" required
                       class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm focus:outline-none focus:ring-2 focus:ring-[#A9552F]">
            </div>
            <div>
                <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1.5">
                    Konfirmasi Kata Sandi
                </label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm focus:outline-none focus:ring-2 focus:ring-[#A9552F]">
            </div>
        </div>

        <div class="p-3.5 rounded-xl bg-[#FAF6EF] border border-[#E6DED2]">
            <label class="flex items-start gap-3 cursor-pointer text-xs text-[#1F1A17]">
                <input type="checkbox" name="consent" value="1" {{ old('consent') ? 'checked' : '' }} required
                       class="mt-0.5 w-4 h-4 rounded border-[#3B2A20] text-[#3B2A20] focus:ring-[#A9552F]">
                <span>
                    <strong>Persetujuan Data Pribadi (UU PDP No. 27/2022 — NFR-11):</strong>
                    Saya menyetujui penyimpanan nama, kontak (email & nomor telepon), dan riwayat transaksi saya oleh 774 HUB semata-mata untuk keperluan pengiriman invoice digital, perhitungan poin loyalti, dan penawaran promosi relevan.
                </span>
            </label>
        </div>

        <button type="submit" class="w-full min-h-[46px] rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-sm font-bold hover:bg-[#2A1E17] transition">
            Daftar & Aktifkan Akun Member
        </button>
    </form>

    <div class="mt-5 pt-4 border-t border-[#E6DED2] text-center text-xs">
        <span class="text-[#6F6259]">Sudah terdaftar sebagai Member?</span>
        <a href="{{ route('login') }}" class="font-bold text-[#A9552F] hover:underline ml-1">Masuk di sini</a>
    </div>
</div>
@endsection
