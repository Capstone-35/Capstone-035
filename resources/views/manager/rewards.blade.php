@extends('layouts.staff', ['title' => 'Katalog Reward Loyalti'])

@section('heading', 'Manager — Katalog Reward Penukaran Poin')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h2 class="text-base font-extrabold text-[#1F1A17]">Daftar Katalog Voucher Reward</h2>
            <p class="text-xs text-[#6F6259]">Voucher yang dapat ditukarkan oleh Member menggunakan saldo poin loyalti.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('manager.loyalty.index') }}" class="px-3.5 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs font-bold text-[#3B2A20] hover:bg-[#F2ECE1]">
                Pengaturan Poin & Pajak
            </a>
            <button type="button" onclick="document.getElementById('reward_form_box').scrollIntoView({behavior: 'smooth'})"
                    class="px-4 py-2 rounded-xl bg-[#3B2A20] text-white text-xs font-bold hover:bg-[#2A1E17]">
                + Tambah Reward Baru
            </button>
        </div>
    </div>

    <!-- Rewards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($rewards as $reward)
            <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm flex flex-col justify-between min-w-0">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold shrink-0 whitespace-nowrap {{ $reward->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">
                            {{ $reward->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-lg bg-[#3B2A20] text-[#FAF6EF] font-extrabold text-xs tabular-nums whitespace-nowrap">
                            {{ number_format($reward->points_cost, 0, ',', '.') }} Poin
                        </span>
                    </div>

                    <h3 class="text-base font-extrabold text-[#1F1A17] mt-2">{{ $reward->name }}</h3>
                    @if($reward->description)
                        <p class="text-xs text-[#6F6259] mt-1">{{ $reward->description }}</p>
                    @endif

                    <div class="mt-3 pt-3 border-t border-[#F2ECE1] space-y-1 text-xs">
                        <div class="flex justify-between gap-2 text-[#6F6259]">
                            <span>Tipe Benefit:</span>
                            <span class="font-bold text-[#1F1A17] tabular-nums">
                                {{ $reward->benefit_type === 'percent' ? $reward->benefit_value . '%' : 'Rp' . number_format($reward->benefit_value, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex justify-between gap-2 text-[#6F6259]">
                            <span>Masa Berlaku Voucher:</span>
                            <span class="font-bold text-[#1F1A17]">{{ $reward->valid_days }} Hari</span>
                        </div>
                        <div class="flex justify-between gap-2 text-[#6F6259]">
                            <span>Total Ditukarkan:</span>
                            <span class="font-bold text-[#1F1A17] tabular-nums">{{ $reward->redemptions_count }}x</span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('manager.rewards.update', ['reward' => $reward->id]) }}" class="mt-4 pt-3 border-t border-[#E6DED2]">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="name" value="{{ $reward->name }}">
                    <input type="hidden" name="description" value="{{ $reward->description }}">
                    <input type="hidden" name="points_cost" value="{{ $reward->points_cost }}">
                    <input type="hidden" name="benefit_type" value="{{ $reward->benefit_type }}">
                    <input type="hidden" name="benefit_value" value="{{ $reward->benefit_value }}">
                    <input type="hidden" name="max_discount" value="{{ $reward->max_discount }}">
                    <input type="hidden" name="valid_days" value="{{ $reward->valid_days }}">
                    <input type="hidden" name="is_active" value="{{ $reward->is_active ? '0' : '1' }}">

                    <button type="submit" class="w-full min-h-[38px] py-1.5 rounded-lg text-xs font-bold {{ $reward->is_active ? 'bg-amber-100 text-amber-900 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-900 hover:bg-emerald-200' }}">
                        {{ $reward->is_active ? 'Nonaktifkan Katalog' : 'Aktifkan Katalog' }}
                    </button>
                </form>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl border border-[#E6DED2] p-8 text-center text-xs text-[#6F6259]">
                Belum ada katalog reward yang tersedia. Gunakan formulir di bawah untuk menambahkan reward baru.
            </div>
        @endforelse
    </div>

    <!-- Create Reward Form Box -->
    <div id="reward_form_box" class="max-w-2xl bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-6 shadow-sm space-y-4">
        <h3 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Tambah Katalog Reward Baru</h3>

        <form method="POST" action="{{ route('manager.rewards.store') }}" class="space-y-4 text-xs">
            @csrf

            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Nama Reward *</label>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="100" placeholder="Contoh: Voucher Diskon Rp10.000"
                       class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold">
            </div>

            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Deskripsi Reward</label>
                <input type="text" name="description" value="{{ old('description') }}" maxlength="255" placeholder="Potongan langsung saat checkout"
                       class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block font-bold uppercase text-[#3B2A20] mb-1">Biaya Poin *</label>
                    <input type="number" name="points_cost" min="1" value="{{ old('points_cost') }}" required placeholder="50"
                           class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold tabular-nums">
                </div>
                <div>
                    <label class="block font-bold uppercase text-[#3B2A20] mb-1">Tipe Benefit *</label>
                    <select name="benefit_type" class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold">
                        <option value="nominal" @selected(old('benefit_type', 'nominal') === 'nominal')>Nominal Potongan (Rp)</option>
                        <option value="percent" @selected(old('benefit_type') === 'percent')>Persentase (%)</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold uppercase text-[#3B2A20] mb-1">Nilai Benefit *</label>
                    <input type="number" name="benefit_value" min="1" value="{{ old('benefit_value') }}" required placeholder="10000"
                           class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold tabular-nums">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold uppercase text-[#3B2A20] mb-1">Masa Aktif Voucher (Hari) *</label>
                    <input type="number" name="valid_days" min="1" max="365" value="{{ old('valid_days', 30) }}" required
                           class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold tabular-nums">
                </div>
                <div>
                    <label class="block font-bold uppercase text-[#3B2A20] mb-1">Maks. Diskon (Opsional)</label>
                    <input type="number" name="max_discount" min="0" value="{{ old('max_discount') }}" placeholder="Kosongkan jika tanpa batas"
                           class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] tabular-nums">
                </div>
            </div>

            <button type="submit" class="w-full sm:w-auto min-h-[44px] px-6 py-2.5 rounded-xl bg-[#3B2A20] text-white font-extrabold hover:bg-[#2A1E17] transition shadow-sm">
                Simpan Katalog Reward
            </button>
        </form>
    </div>
</div>
@endsection
