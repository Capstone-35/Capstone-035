@extends('layouts.customer', ['title' => 'Selamat Datang di ' . $table->label])

@section('content')
<div class="max-w-lg mx-auto bg-white rounded-2xl border border-[#E6DED2] p-5 sm:p-7 shadow-sm text-center">
    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#DCFCE7] text-[#166534] text-xs font-extrabold">
        <span class="w-2 h-2 rounded-full bg-[#166534]"></span>
        QR Meja Terverifikasi • {{ $table->label }}
    </span>

    <h1 class="mt-4 text-2xl font-extrabold text-[#1F1A17]">Selamat Datang di 774 HUB</h1>
    <p class="mt-2 text-sm text-[#6F6259]">
        Nomor meja Anda (<strong>{{ $table->label }}</strong>) telah terisi otomatis. Silakan pilih cara pemesanan:
    </p>

    @if(auth()->check() && auth()->user()->hasRole('member'))
        <div class="mt-6 p-4 rounded-xl bg-[#F2ECE1] border border-[#E6DED2] text-left">
            <div class="text-xs font-bold text-[#6F6259] uppercase">Member Aktif</div>
            <div class="text-base font-extrabold text-[#1F1A17] mt-0.5">{{ auth()->user()->name }}</div>
            <div class="text-xs text-[#A9552F] font-semibold mt-0.5">
                Saldo Poin: {{ number_format(auth()->user()->member?->points_balance ?? 0, 0, ',', '.') }} Poin
            </div>
        </div>
        <div class="mt-4">
            <a href="{{ route('menu.index') }}" class="flex items-center justify-center w-full min-h-[48px] rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-sm font-bold hover:bg-[#2A1E17] transition">
                Lihat Menu & Pesan Sekarang →
            </a>
        </div>
    @else
        <div class="mt-6 space-y-3">
            <form method="POST" action="{{ route('table.continue_guest', ['qr_token' => $table->qr_token]) }}">
                @csrf
                <button type="submit" class="w-full min-h-[48px] rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-sm font-bold hover:bg-[#2A1E17] transition">
                    Pesan Langsung sebagai Tamu (Guest)
                </button>
            </form>

            <div class="flex items-center gap-3 py-2">
                <div class="flex-1 border-t border-[#E6DED2]"></div>
                <span class="text-xs font-semibold text-[#6F6259] shrink-0">Atau gunakan akun Member untuk Poin & Promo</span>
                <div class="flex-1 border-t border-[#E6DED2]"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <a href="{{ route('login') }}" class="flex items-center justify-center min-h-[46px] rounded-xl border-2 border-[#3B2A20] text-[#3B2A20] text-sm font-bold hover:bg-[#F2ECE1] transition">
                    Masuk sebagai Member
                </a>
                <a href="{{ route('register') }}" class="flex items-center justify-center min-h-[46px] rounded-xl bg-[#A9552F] text-white text-sm font-bold hover:bg-[#8E4423] transition">
                    Daftar Member Baru
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
