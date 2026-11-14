@extends('layouts.customer', ['title' => 'Gangguan Koneksi'])

@section('content')
<div class="bg-white rounded-2xl border border-[#E6DED2] p-6 text-center shadow-sm">
    <div class="mx-auto w-14 h-14 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center font-extrabold text-lg">
        !
    </div>
    <h1 class="mt-4 text-xl font-extrabold text-[#1F1A17]">Koneksi Terputus</h1>
    <p class="mt-2 text-sm text-[#6F6259]">
        Periksa sambungan internet atau Wi-Fi 774 HUB Anda. Keranjang pesanan dan status transaksi Anda tersimpan aman di server.
    </p>
    <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
        <a href="{{ route('menu.index') }}" class="inline-flex items-center justify-center min-h-[44px] px-6 py-2.5 rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-sm font-bold">
            Coba Muat Ulang Menu
        </a>
    </div>
</div>
@endsection
