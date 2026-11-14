@extends('layouts.staff', ['title' => ($isEdit ? 'Ubah' : 'Tambah') . ' Promosi'])

@section('heading', 'Manager — ' . ($isEdit ? 'Ubah' : 'Tambah') . ' Promosi Behavioral')

@section('content')
<div class="max-w-3xl bg-white rounded-2xl border border-[#E6DED2] p-6 shadow-sm space-y-6">
    <div class="flex items-center justify-between border-b border-[#F2ECE1] pb-3">
        <div>
            <a href="{{ route('manager.promotions.index') }}" class="text-xs font-bold text-[#6F6259] hover:text-[#3B2A20]">
                ← Kembali ke Daftar Promosi
            </a>
            <h2 class="text-lg font-extrabold text-[#1F1A17] mt-1">{{ $isEdit ? 'Ubah Parameter Promosi' : 'Buat Promosi Behavioral Baru' }}</h2>
        </div>
    </div>

    <form method="POST" action="{{ $isEdit ? route('manager.promotions.update', ['promotion' => $promotion->id]) : route('manager.promotions.store') }}" class="space-y-4 text-xs">
        @csrf
        @if($isEdit)
            @method('PATCH')
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Kode Voucher / Promo *</label>
                <input type="text" name="code" value="{{ old('code', $promotion->code) }}" required maxlength="40"
                       class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-mono font-bold uppercase">
            </div>

            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Nama Promosi *</label>
                <input type="text" name="name" value="{{ old('name', $promotion->name) }}" required maxlength="100"
                       class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold">
            </div>
        </div>

        <div>
            <label class="block font-bold uppercase text-[#3B2A20] mb-1">Jenis Aturan Behavioral (D1 — 6 Aturan) *</label>
            <select name="rule_type" required class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold text-sm">
                @foreach($ruleTypes as $rt)
                    <option value="{{ $rt->value }}" {{ old('rule_type', $promotion->rule_type?->value) === $rt->value ? 'selected' : '' }}>
                        {{ $rt->label() }} ({{ $rt->value }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block font-bold uppercase text-[#3B2A20] mb-1">Deskripsi & Syarat Ringkas</label>
            <textarea name="description" rows="2" class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">{{ old('description', $promotion->description) }}</textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Tipe Benefit *</label>
                <select name="benefit_type" class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold">
                    <option value="percent" {{ old('benefit_type', $promotion->benefit_type) === 'percent' ? 'selected' : '' }}>Persentase Diskon (%)</option>
                    <option value="nominal" {{ old('benefit_type', $promotion->benefit_type) === 'nominal' ? 'selected' : '' }}>Nominal Potongan (Rp)</option>
                    <option value="bonus_points" {{ old('benefit_type', $promotion->benefit_type) === 'bonus_points' ? 'selected' : '' }}>Bonus Poin Loyalti</option>
                </select>
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Nilai Benefit *</label>
                <input type="number" name="benefit_value" min="1" value="{{ old('benefit_value', $promotion->benefit_value) }}" required
                       class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold tabular-nums">
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Maks. Diskon (Rp, Opsional)</label>
                <input type="number" name="max_discount" value="{{ old('max_discount', $promotion->max_discount) }}"
                       class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] tabular-nums">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Mulai Berlaku *</label>
                <input type="date" name="starts_at" value="{{ old('starts_at', $promotion->starts_at?->format('Y-m-d') ?? now()->toDateString()) }}" required
                       class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Berakhir Pada *</label>
                <input type="date" name="ends_at" value="{{ old('ends_at', $promotion->ends_at?->format('Y-m-d') ?? now()->addDays(30)->toDateString()) }}" required
                       class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Minimal Transaksi (Rp) *</label>
                <input type="number" name="min_transaction" value="{{ old('min_transaction', $promotion->min_transaction ?? 0) }}" step="1000" required
                       class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold tabular-nums">
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Batas Pakai per Member *</label>
                <input type="number" name="per_member_limit" min="1" value="{{ old('per_member_limit', $promotion->per_member_limit ?? 1) }}" required
                       class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold tabular-nums">
            </div>
        </div>

        <div class="p-3.5 rounded-xl bg-[#FAF6EF] border border-[#E6DED2]">
            <label class="flex items-center gap-2 cursor-pointer font-bold text-xs text-[#1F1A17]">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $promotion->is_active) ? 'checked' : '' }}
                       class="rounded border-[#3B2A20] text-[#1F7A4C]">
                <span>Status Aktif (Dapat langsung digunakan saat checkout sesuai kriteria)</span>
            </label>
        </div>

        <div class="pt-3 border-t border-[#F2ECE1] flex justify-end gap-2">
            <a href="{{ route('manager.promotions.index') }}" class="px-4 py-2.5 rounded-xl border border-[#D6CBBC] font-bold text-[#6F6259]">
                Batal
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#3B2A20] text-white font-extrabold hover:bg-[#2A1E17]">
                {{ $isEdit ? 'Simpan Perubahan Promosi' : 'Buat Promosi' }}
            </button>
        </div>
    </form>
</div>
@endsection
