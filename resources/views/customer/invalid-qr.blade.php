@extends('layouts.customer', ['title' => 'Kode QR Meja Tidak Valid'])

@section('content')
<div class="max-w-lg mx-auto bg-white rounded-2xl border border-[#E6DED2] p-5 sm:p-7 text-center shadow-sm">
    <div class="mx-auto w-14 h-14 rounded-2xl bg-[#FEE2E2] text-[#991B1B] flex items-center justify-center font-extrabold text-xl">
        QR
    </div>
    <h1 class="mt-4 text-xl font-extrabold text-[#1F1A17]">Kode QR Meja Tidak Ditemukan atau Sudah Diganti</h1>
    <p class="mt-2 text-sm text-[#6F6259]">
        Silakan pindai ulang kode QR terbaru yang tertera di meja Anda, atau hubungi Kasir 774 HUB untuk bantuan pemesanan.
    </p>

    @php
        $demoTables = \App\Models\DiningTable::query()->where('is_active', true)->orderBy('number')->limit(6)->get();
    @endphp
    @if($demoTables->isNotEmpty())
        <div class="mt-6 pt-5 border-t border-[#E6DED2]">
            <div class="text-xs font-bold text-[#3B2A20] mb-3">Pilih Meja Aktif (Simulasi Scan QR):</div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                @foreach($demoTables as $t)
                    <a href="{{ route('table.scan', ['qr_token' => $t->qr_token]) }}"
                       class="min-h-[44px] flex items-center justify-center rounded-xl bg-[#F2ECE1] hover:bg-[#3B2A20] hover:text-white text-xs font-bold text-[#3B2A20] transition">
                        {{ $t->label }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
