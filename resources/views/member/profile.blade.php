@extends('layouts.customer', ['title' => 'Profil & Keamanan Akun Member'])

@section('content')
<div class="space-y-4">
    <!-- Header & Navigation -->
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h1 class="text-xl font-extrabold text-[#1F1A17]">Profil & Keamanan Akun</h1>
            <p class="text-xs text-[#6F6259]">Kelola data pribadi, kata sandi, dan sesi akun Member 774 HUB Anda.</p>
        </div>
        <a href="{{ route('member.dashboard') }}" class="text-xs font-bold text-[#6F6259] hover:text-[#3B2A20] inline-flex items-center gap-1">
            <span>←</span> Kembali ke Portal Member
        </a>
    </div>

    <!-- Form 1: Data Profil Pribadi -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-4">
        <div class="border-b border-[#F2ECE1] pb-3 flex items-center justify-between">
            <h2 class="text-base font-extrabold text-[#1F1A17]">Data Diri Member</h2>
            <span class="px-2.5 py-1 rounded-lg bg-[#FAF6EF] border border-[#E6DED2] text-xs font-mono font-bold text-[#3B2A20]">
                {{ $member->member_code }}
            </span>
        </div>

        <form method="POST" action="{{ route('member.profile.update') }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1">Nama Lengkap</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                       class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#A9552F]">
            </div>

            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1">Alamat Email (Pengiriman Invoice Digital)</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                       class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#A9552F]">
            </div>

            <div>
                <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1">Nomor WhatsApp / Telepon</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}" required
                       class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#A9552F]">
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full min-h-[46px] rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-sm font-bold hover:bg-[#2A1E17] transition shadow-sm">
                    Simpan Perubahan Profil
                </button>
            </div>
        </form>
    </div>

    <!-- Form 2: Ganti Kata Sandi -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-4">
        <div class="border-b border-[#F2ECE1] pb-3">
            <h2 class="text-base font-extrabold text-[#1F1A17]">Ganti Kata Sandi</h2>
            <p class="text-xs text-[#6F6259] mt-0.5">Perbarui kata sandi Anda secara berkala untuk menjaga keamanan akun.</p>
        </div>

        <form method="POST" action="{{ route('member.profile.password') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1">Kata Sandi Saat Ini</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                       class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm">
            </div>

            <div>
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1">Kata Sandi Baru (Min. 8 Karakter)</label>
                <input id="password" name="password" type="password" required autocomplete="new-password"
                       class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm">
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-[#3B2A20] mb-1">Konfirmasi Kata Sandi Baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                       class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm">
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full min-h-[46px] rounded-xl bg-[#A9552F] text-white text-sm font-bold hover:bg-[#8E4423] transition shadow-sm">
                    Perbarui Kata Sandi
                </button>
            </div>
        </form>
    </div>

    <!-- Sesi & Logout -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-extrabold text-[#1F1A17]">Sesi Masuk Akun</h2>
                <p class="text-xs text-[#6F6259]">Keluar dari akun Member pada perangkat ini.</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Apakah Anda yakin ingin keluar dari akun Member?');">
                @csrf
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 text-white text-xs font-bold hover:bg-red-700 transition shadow-sm">
                    Keluar (Logout)
                </button>
            </form>
        </div>
    </div>

    <!-- Kepatuhan UU PDP -->
    <div class="bg-[#F2ECE1] rounded-2xl border border-[#E6DED2] p-5 text-xs text-[#3B2A20] space-y-1.5">
        <div class="font-extrabold text-[#1F1A17]">Kepatuhan Perlindungan Data Pribadi (UU PDP No. 27 Tahun 2022 — NFR-11)</div>
        <p class="text-[#6F6259]">
            Data pribadi yang disimpan oleh 774 HUB terbatas pada nama, kontak (email dan nomor telepon), serta riwayat transaksi untuk keperluan pengiriman invoice digital, poin loyalti, dan promosi berbasis RFM.
        </p>
        <div class="font-semibold text-[#1F1A17] pt-1">
            Waktu Persetujuan Tercatat: {{ $member->consent_at ? $member->consent_at->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB' : 'Tercatat saat pendaftaran' }}
        </div>
    </div>
</div>
@endsection
