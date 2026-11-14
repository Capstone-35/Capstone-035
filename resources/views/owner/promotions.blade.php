@extends('layouts.staff', ['title' => 'Performa Promosi Owner'])

@section('heading', 'Owner — Evaluasi Performa Promosi & Return Customer (Read-Only)')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm">
        <h2 class="text-base font-extrabold text-[#1F1A17]">Efektivitas Promosi Behavioral Terhadap Retensi Pelanggan</h2>
        <p class="text-xs text-[#6F6259]">Memantau apakah diskon promosi menghasilkan repeat order nyata (Promo Return Tracking Rule 6).</p>
    </div>

    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3">Nama Promosi</th>
                        <th class="px-4 py-3">Aturan Behavioral</th>
                        <th class="px-4 py-3 text-center">Penggunaan</th>
                        <th class="px-4 py-3 text-right">Diskon Diberikan</th>
                        <th class="px-4 py-3 text-right">Pendapatan Masuk</th>
                        <th class="px-4 py-3 text-center">Tingkat Return (Retensi)</th>
                        <th class="px-4 py-3 text-center">Rata-rata Waktu Kembali</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @foreach($performance as $pf)
                        <tr class="hover:bg-[#FAF6EF]/60">
                            <td class="px-4 py-3.5 font-bold text-[#1F1A17]">{{ $pf['promotion']->name }}</td>
                            <td class="px-4 py-3.5 text-[#6F6259]">{{ $pf['promotion']->rule_type->label() }}</td>
                            <td class="px-4 py-3.5 text-center font-bold tabular-nums">{{ $pf['times_used'] }}x</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-[#1F7A4C] font-bold">Rp{{ number_format($pf['total_discount'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums font-black text-[#1F1A17]">Rp{{ number_format($pf['orders_revenue'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="px-2.5 py-1 rounded-full text-xs font-black {{ $pf['return_rate'] >= 50 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900' }}">
                                    {{ $pf['return_rate'] }}%
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center font-bold text-[#3B2A20]">
                                {{ $pf['avg_days_to_return'] !== null ? $pf['avg_days_to_return'] . ' Hari' : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
