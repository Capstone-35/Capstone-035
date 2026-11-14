@extends('layouts.staff', ['title' => 'Pengaturan Loyalti & Sistem'])

@section('heading', 'Manager — Pengaturan Loyalti, Pajak, dan Parameter QRIS')

@section('content')
<div class="max-w-2xl bg-white rounded-2xl border border-[#E6DED2] p-6 shadow-sm space-y-6">
    <div class="border-b border-[#F2ECE1] pb-3">
        <h2 class="text-lg font-extrabold text-[#1F1A17]">Pengaturan Poin Loyalti & Parameter Transaksi</h2>
        <p class="text-xs text-[#6F6259]">Atur konversi poin royalti, syarat pemberian poin, serta persentase pajak.</p>
    </div>

    <form method="POST" action="{{ route('manager.loyalty.update') }}" class="space-y-5 text-xs">
        @csrf
        @method('PATCH')

        <div>
            <label class="block font-bold uppercase text-[#3B2A20] mb-1">
                Kelipatan Rupiah untuk 1 Poin Loyalti (Assumption A2) *
            </label>
            <input type="number" name="points_per_amount" min="1000" step="1000" value="{{ old('points_per_amount', $pointsPerAmount) }}" required
                   class="w-full px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold text-sm tabular-nums">
            <span class="text-[11px] text-[#6F6259]">Default: Rp10.000 (1 Poin diberikan setiap pembelanjaan Rp10.000).</span>
        </div>

        <div>
            <label class="block font-bold uppercase text-[#3B2A20] mb-1">
                Status Transaksi Pemicu Pemberian Poin & RFM (D4) *
            </label>
            <select name="earn_on_status" required class="w-full px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold text-sm">
                <option value="Selesai" {{ old('earn_on_status', $earnOnStatus) === 'Selesai' ? 'selected' : '' }}>
                    Selesai (Pesanan sudah diserahkan kepada pelanggan — Default D4)
                </option>
                <option value="Dibayar" {{ old('earn_on_status', $earnOnStatus) === 'Dibayar' ? 'selected' : '' }}>
                    Dibayar (Poin langsung diberikan saat status Dibayar)
                </option>
            </select>
        </div>

        <div>
            <label class="block font-bold uppercase text-[#3B2A20] mb-1">
                Persentase Pajak Transaksi (D2, tax_rate %) *
            </label>
            <input type="number" name="tax_rate" min="0" max="30" step="0.5" value="{{ old('tax_rate', $taxRate) }}" required
                   class="w-full px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold text-sm tabular-nums">
            <span class="text-[11px] text-[#6F6259]">Jika bernilai 0, baris pajak disembunyikan otomatis pada checkout & invoice (D2).</span>
        </div>

        <div>
            <label class="block font-bold uppercase text-[#3B2A20] mb-1">
                Batas Waktu Kedaluwarsa QRIS (Menit — Assumption A1) *
            </label>
            <input type="number" name="qris_expiry_minutes" min="1" max="120" value="{{ old('qris_expiry_minutes', $qrisExpiryMinutes) }}" required
                   class="w-full px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold text-sm tabular-nums">
            <span class="text-[11px] text-[#6F6259]">Batas waktu pembayaran QRIS sebelum ditandai kedaluwarsa (Standar: 15 menit).</span>
        </div>

        <button type="submit" class="px-6 py-3 rounded-xl bg-[#3B2A20] text-white text-xs font-extrabold hover:bg-[#2A1E17] transition shadow-sm">
            Simpan Konfigurasi Sistem
        </button>
    </form>
</div>
@endsection
