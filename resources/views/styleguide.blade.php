@extends('layouts.staff', ['title' => 'Design System & Styleguide'])

@section('heading', 'Design System & Reusable Components (Section 11.3)')

@section('content')
<div class="space-y-8">
    <section class="bg-white rounded-2xl border border-[#E6DED2] p-6">
        <h2 class="text-base font-extrabold text-[#1F1A17] mb-4">1. Palet Warna 774 HUB (Warm Coffee Shop Identity)</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="p-4 rounded-xl bg-[#FAF6EF] border border-[#E6DED2] text-xs font-bold">#FAF6EF — Background</div>
            <div class="p-4 rounded-xl bg-[#F2ECE1] border border-[#E6DED2] text-xs font-bold">#F2ECE1 — Surface Muted</div>
            <div class="p-4 rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-bold">#3B2A20 — Primary Espresso</div>
            <div class="p-4 rounded-xl bg-[#A9552F] text-white text-xs font-bold">#A9552F — Accent Terracotta</div>
            <div class="p-4 rounded-xl bg-[#1F7A4C] text-white text-xs font-bold">#1F7A4C — Success</div>
            <div class="p-4 rounded-xl bg-[#B91C1C] text-white text-xs font-bold">#B91C1C — Danger</div>
        </div>
    </section>

    <section class="bg-white rounded-2xl border border-[#E6DED2] p-6">
        <h2 class="text-base font-extrabold text-[#1F1A17] mb-4">2. Status Badge Transaksi (FR-04)</h2>
        <div class="flex flex-wrap gap-3">
            @foreach($statuses as $status)
                <x-status-badge :status="$status" />
            @endforeach
        </div>
    </section>

    <section class="bg-white rounded-2xl border border-[#E6DED2] p-6">
        <h2 class="text-base font-extrabold text-[#1F1A17] mb-4">3. KPI Cards</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-kpi-card label="Total Pendapatan" value="Rp14.850.000" subtext="30 Hari Terakhir" badge="Tunai & QRIS" :accent="true" />
            <x-kpi-card label="Transaksi Berhasil" value="312" subtext="Order::successful()" badge="+18%" />
            <x-kpi-card label="Rata-rata Waktu Sajian" value="4,2 Menit" subtext="Dibayar → Siap" />
        </div>
    </section>

    <section class="bg-white rounded-2xl border border-[#E6DED2] p-6">
        <h2 class="text-base font-extrabold text-[#1F1A17] mb-4">4. Empty State Component</h2>
        <x-empty-state
            title="Belum Ada Transaksi pada Filter Ini"
            description="Coba ubah rentang tanggal atau status transaksi untuk melihat data operasional."
            :action-url="route('manager.dashboard')"
            action-label="Kembali ke Dashboard"
        />
    </section>
</div>
@endsection
