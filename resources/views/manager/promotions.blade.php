@extends('layouts.staff', ['title' => 'Manajemen Promosi'])

@section('heading', 'Manager — Manajemen Behavioral Promotion Engine (Module J)')

@section('content')
<div class="space-y-4">
    <!-- Action Bar -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h2 class="text-base font-extrabold text-[#1F1A17]">Daftar 6 Aturan Promosi Behavioral (D1)</h2>
            <p class="text-xs text-[#6F6259]">Comeback, Repeat Order, Hari Besar, Welcome/Next Visit, Slow Hour, Return Tracking.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('manager.promotions.performance') }}" class="px-3.5 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs font-bold text-[#3B2A20] hover:bg-[#E6DED2]">
                Evaluasi Performa & Return Tracking
            </a>
            <a href="{{ route('manager.promotions.create') }}" class="px-4 py-2 rounded-xl bg-[#3B2A20] text-white text-xs font-bold hover:bg-[#2A1E17]">
                + Tambah Promosi
            </a>
        </div>
    </div>

    <!-- Promotions Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($promotions as $promo)
            <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm flex flex-col justify-between min-w-0">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold shrink-0 whitespace-nowrap {{ $promo->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }}">
                            {{ $promo->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                        <span class="font-mono font-bold text-xs text-[#A9552F] break-all text-right">{{ $promo->code }}</span>
                    </div>

                    <h3 class="text-base font-extrabold text-[#1F1A17] mt-2">{{ $promo->name }}</h3>
                    <div class="text-xs font-semibold text-[#A9552F]">{{ $promo->rule_type?->label() ?? '—' }}</div>
                    <p class="text-xs text-[#6F6259] mt-1 line-clamp-2">{{ $promo->description }}</p>

                    <div class="mt-3 pt-3 border-t border-[#F2ECE1] space-y-1 text-xs">
                        <div class="flex justify-between gap-2 text-[#6F6259]">
                            <span>Benefit:</span>
                            <span class="font-bold text-[#1F1A17] text-right">{{ $promo->benefitSummary() }}</span>
                        </div>
                        <div class="flex justify-between gap-2 text-[#6F6259]">
                            <span>Min. Transaksi:</span>
                            <span class="font-bold text-[#1F1A17] tabular-nums">Rp{{ number_format($promo->min_transaction, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between gap-2 text-[#6F6259]">
                            <span>Total Terpakai:</span>
                            <span class="font-bold text-[#1F1A17] tabular-nums">{{ $promo->usages_count }}x Transaksi</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-[#E6DED2] flex flex-wrap items-center justify-between gap-2">
                    <form method="POST" action="{{ route('manager.promotions.toggle', ['promotion' => $promo->id]) }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold {{ $promo->is_active ? 'bg-amber-100 text-amber-900 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-900 hover:bg-emerald-200' }}">
                            {{ $promo->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </form>

                    <div class="flex items-center gap-1.5">
                        <a href="{{ route('manager.promotions.edit', ['promotion' => $promo->id]) }}" class="px-3 py-1.5 rounded-lg border border-[#D6CBBC] text-xs font-bold hover:bg-[#F2ECE1]">
                            Ubah
                        </a>
                        <a href="{{ route('manager.promotions.show', ['promotion' => $promo->id]) }}" class="px-3 py-1.5 rounded-lg bg-[#3B2A20] text-white text-xs font-bold hover:bg-[#2A1E17]">
                            Detail
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl border border-[#E6DED2] p-8 text-center text-xs text-[#6F6259]">
                Belum ada promosi yang terdaftar. Klik "+ Tambah Promosi" untuk membuat promo baru.
            </div>
        @endforelse
    </div>
</div>
@endsection
