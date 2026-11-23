@extends('layouts.staff', ['title' => 'Manajemen Meja & QR'])

@section('heading', 'Manager — Manajemen Meja & Kode QR (FR-01)')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
    <!-- Tables Grid -->
    <div class="xl:col-span-8 bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#F2ECE1] pb-3">
            <div class="min-w-0">
                <h3 class="text-base font-extrabold text-[#1F1A17]">Daftar Meja Pelanggan</h3>
                <p class="text-xs text-[#6F6259]">Setiap meja memiliki kode token QR unik yang mengisi nomor meja secara otomatis (FR-01).</p>
            </div>
            <span class="px-3 py-1 rounded-full bg-[#3B2A20] text-white font-bold text-xs whitespace-nowrap shrink-0">{{ $tables->count() }} Meja</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            @forelse($tables as $t)
                <div class="p-4 rounded-xl border border-[#E6DED2] bg-[#FAF6EF] flex flex-col justify-between min-w-0">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <span class="font-extrabold text-base text-[#1F1A17] break-words">{{ $t->label }}</span>
                                <div class="text-[11px] text-[#6F6259]">Nomor: <strong>{{ $t->number }}</strong> • {{ $t->orders_count }} Pesanan</div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold shrink-0 whitespace-nowrap {{ $t->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700' }}">
                                {{ $t->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                        <div class="mt-2 font-mono text-[10px] text-[#6F6259] truncate" title="{{ $t->qr_token }}">Token: {{ $t->qr_token }}</div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-[#E6DED2] flex flex-wrap items-center justify-between gap-2">
                        <a href="{{ route('manager.tables.qr', ['table' => $t->id]) }}" target="_blank"
                           class="px-3 py-1.5 rounded-lg bg-[#3B2A20] text-white text-xs font-bold hover:bg-[#2A1E17] whitespace-nowrap">
                            Unduh SVG QR
                        </a>
                        <form method="POST" action="{{ route('manager.tables.regenerate', ['table' => $t->id]) }}"
                              onsubmit="return confirm('Perbarui token QR untuk meja ini? Kode QR lama akan menjadi tidak valid.');">
                            @csrf
                            <button type="submit" class="px-2.5 py-1.5 rounded-lg border border-[#D6CBBC] bg-white text-xs font-bold text-[#A9552F] hover:bg-[#F2ECE1] whitespace-nowrap">
                                Regenerasi QR
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-8 text-center text-xs text-[#6F6259]">
                    Belum ada meja yang terdaftar. Tambahkan meja baru di panel sebelah.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Create Table Form -->
    <div class="xl:col-span-4 bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-4">
        <h3 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Tambah Meja Baru</h3>
        <form method="POST" action="{{ route('manager.tables.store') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Nomor Meja *</label>
                <input type="text" name="number" value="{{ old('number') }}" required maxlength="10" pattern="[A-Za-z0-9\-]+"
                       title="Hanya huruf, angka, atau tanda hubung (-)" placeholder="Contoh: 13 atau A-01"
                       class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold">
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Label Tampilan *</label>
                <input type="text" name="label" value="{{ old('label') }}" required maxlength="50" placeholder="Contoh: Meja 13 (Indoor)"
                       class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <button type="submit" class="w-full min-h-[44px] rounded-xl bg-[#3B2A20] text-white font-extrabold hover:bg-[#2A1E17] transition mt-2">
                Buat Meja & Generate QR
            </button>
        </form>
    </div>
</div>
@endsection
