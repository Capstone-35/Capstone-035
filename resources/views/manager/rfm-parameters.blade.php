@extends('layouts.staff', ['title' => 'Parameter Skor RFM'])

@section('heading', 'Manager — Konfigurasi Parameter Skor RFM (FR-12, NFR-17)')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm">
        <a href="{{ route('manager.rfm.index') }}" class="text-xs font-bold text-[#6F6259] hover:text-[#3B2A20]">
            ← Kembali ke Analisis RFM
        </a>
        <h2 class="text-base sm:text-lg font-extrabold text-[#1F1A17] mt-1">Konfigurasi Ambang Batas Skor RFM (Skala 1–5)</h2>
        <p class="text-xs text-[#6F6259]">
            Ambang batas skor bersifat monotonik dan deterministik (100% konsisten dengan perhitungan manual).
        </p>
    </div>

    <form method="POST" action="{{ route('manager.rfm.parameters.update') }}" class="space-y-5 sm:space-y-6">
        @csrf
        @method('PATCH')

        <!-- Period Days -->
        <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-2">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">1. Jendela Analisis RFM (Assumption A3)</h3>
            <div class="max-w-xs">
                <label class="block text-xs font-bold text-[#3B2A20] mb-1">Periode Evaluasi (Hari)</label>
                <input type="number" name="period_days" min="7" max="730" required
                       value="{{ old('period_days', $parameters['period_days'] ?? 90) }}"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-sm font-bold tabular-nums">
                <span class="text-[11px] text-[#6F6259]">Standar 774 HUB: 90 Hari (antara 7 sampai 730 hari).</span>
            </div>
        </div>

        <!-- R Thresholds -->
        <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-3">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">2. Ambang Recency (Hari Sejak Order Terakhir — Nilai Lebih Kecil = Lebih Baik)</h3>
            <p class="text-xs text-[#6F6259]">Skor 5 &le; R[5], Skor 4 &le; R[4], Skor 3 &le; R[3], Skor 2 &le; R[2], selainnya Skor 1.</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div>
                    <label class="block font-bold text-[#1F7A4C] mb-1">Skor 5 (&le; Hari)</label>
                    <input type="number" name="r_thresholds[5]" min="1" value="{{ old('r_thresholds.5', $parameters['r_thresholds'][5] ?? 7) }}" required
                           class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold tabular-nums">
                </div>
                <div>
                    <label class="block font-bold text-[#1F1A17] mb-1">Skor 4 (&le; Hari)</label>
                    <input type="number" name="r_thresholds[4]" min="1" value="{{ old('r_thresholds.4', $parameters['r_thresholds'][4] ?? 14) }}" required
                           class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold tabular-nums">
                </div>
                <div>
                    <label class="block font-bold text-[#1F1A17] mb-1">Skor 3 (&le; Hari)</label>
                    <input type="number" name="r_thresholds[3]" min="1" value="{{ old('r_thresholds.3', $parameters['r_thresholds'][3] ?? 30) }}" required
                           class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold tabular-nums">
                </div>
                <div>
                    <label class="block font-bold text-[#B91C1C] mb-1">Skor 2 (&le; Hari)</label>
                    <input type="number" name="r_thresholds[2]" min="1" value="{{ old('r_thresholds.2', $parameters['r_thresholds'][2] ?? 60) }}" required
                           class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold tabular-nums">
                </div>
            </div>
        </div>

        <!-- F Thresholds -->
        <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-3">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">3. Ambang Frequency (Jumlah Transaksi Berhasil — Nilai Lebih Besar = Lebih Baik)</h3>
            <p class="text-xs text-[#6F6259]">Skor 5 &ge; F[5], Skor 4 &ge; F[4], Skor 3 &ge; F[3], Skor 2 &ge; F[2], selainnya Skor 1.</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div>
                    <label class="block font-bold text-[#1F7A4C] mb-1">Skor 5 (&ge; Transaksi)</label>
                    <input type="number" name="f_thresholds[5]" min="1" value="{{ old('f_thresholds.5', $parameters['f_thresholds'][5] ?? 8) }}" required
                           class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold tabular-nums">
                </div>
                <div>
                    <label class="block font-bold text-[#1F1A17] mb-1">Skor 4 (&ge; Transaksi)</label>
                    <input type="number" name="f_thresholds[4]" min="1" value="{{ old('f_thresholds.4', $parameters['f_thresholds'][4] ?? 5) }}" required
                           class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold tabular-nums">
                </div>
                <div>
                    <label class="block font-bold text-[#1F1A17] mb-1">Skor 3 (&ge; Transaksi)</label>
                    <input type="number" name="f_thresholds[3]" min="1" value="{{ old('f_thresholds.3', $parameters['f_thresholds'][3] ?? 3) }}" required
                           class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold tabular-nums">
                </div>
                <div>
                    <label class="block font-bold text-[#B91C1C] mb-1">Skor 2 (&ge; Transaksi)</label>
                    <input type="number" name="f_thresholds[2]" min="1" value="{{ old('f_thresholds.2', $parameters['f_thresholds'][2] ?? 2) }}" required
                           class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold tabular-nums">
                </div>
            </div>
        </div>

        <!-- M Thresholds -->
        <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-3">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">4. Ambang Monetary (Total Belanja Rupiah — Nilai Lebih Besar = Lebih Baik)</h3>
            <p class="text-xs text-[#6F6259]">Skor 5 &ge; M[5], Skor 4 &ge; M[4], Skor 3 &ge; M[3], Skor 2 &ge; M[2], selainnya Skor 1.</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div>
                    <label class="block font-bold text-[#1F7A4C] mb-1">Skor 5 (&ge; Rp)</label>
                    <input type="number" name="m_thresholds[5]" min="1000" value="{{ old('m_thresholds.5', $parameters['m_thresholds'][5] ?? 1000000) }}" required
                           class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold tabular-nums">
                </div>
                <div>
                    <label class="block font-bold text-[#1F1A17] mb-1">Skor 4 (&ge; Rp)</label>
                    <input type="number" name="m_thresholds[4]" min="1000" value="{{ old('m_thresholds.4', $parameters['m_thresholds'][4] ?? 600000) }}" required
                           class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold tabular-nums">
                </div>
                <div>
                    <label class="block font-bold text-[#1F1A17] mb-1">Skor 3 (&ge; Rp)</label>
                    <input type="number" name="m_thresholds[3]" min="1000" value="{{ old('m_thresholds.3', $parameters['m_thresholds'][3] ?? 300000) }}" required
                           class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold tabular-nums">
                </div>
                <div>
                    <label class="block font-bold text-[#B91C1C] mb-1">Skor 2 (&ge; Rp)</label>
                    <input type="number" name="m_thresholds[2]" min="1000" value="{{ old('m_thresholds.2', $parameters['m_thresholds'][2] ?? 100000) }}" required
                           class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold tabular-nums">
                </div>
            </div>
        </div>

        <button type="submit" class="w-full sm:w-auto min-h-[44px] px-6 py-3 rounded-xl bg-[#3B2A20] text-white text-xs font-extrabold hover:bg-[#2A1E17] transition shadow-sm">
            Simpan Parameter & Hitung Ulang Analisis RFM
        </button>
    </form>
</div>
@endsection
