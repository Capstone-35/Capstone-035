@extends('layouts.staff', ['title' => 'Performa Promosi & Return Tracking'])

@section('heading', 'Manager — Evaluasi & Performa Promosi (Promo Return Tracking)')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm">
        <a href="{{ route('manager.promotions.index') }}" class="text-xs font-bold text-[#6F6259] hover:text-[#3B2A20]">
            ← Kembali ke Manajemen Promosi
        </a>
        <h2 class="text-lg font-extrabold text-[#1F1A17] mt-1">Evaluasi Keefektifan Promosi & Pelacakan Return Customer (Rule 6)</h2>
        <p class="text-xs text-[#6F6259]">
            Melacak seberapa cepat member kembali melakukan repeat order setelah menerima promosi (Conversion Return Rate).
        </p>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3">Promosi</th>
                        <th class="px-4 py-3">Jenis Aturan</th>
                        <th class="px-4 py-3 text-center">Dipakai (x)</th>
                        <th class="px-4 py-3 text-right">Total Diskon Diberikan</th>
                        <th class="px-4 py-3 text-right">Total Revenue Masuk</th>
                        <th class="px-4 py-3 text-center">Customer Return Rate</th>
                        <th class="px-4 py-3 text-center">Rata-rata Kembali</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($performance as $perf)
                        <tr class="hover:bg-[#FAF6EF]/60 transition">
                            <td class="px-4 py-3.5 font-bold text-[#1F1A17]">
                                <a href="{{ route('manager.promotions.show', ['promotion' => $perf['promotion']->id]) }}" class="hover:underline">
                                    {{ $perf['promotion']->name }}
                                </a>
                                <div class="font-mono text-[10px] text-[#A9552F]">{{ $perf['promotion']->code }}</div>
                            </td>
                            <td class="px-4 py-3.5 text-[#6F6259]">{{ $perf['promotion']->rule_type->label() }}</td>
                            <td class="px-4 py-3.5 text-center font-bold tabular-nums">{{ $perf['times_used'] }}</td>
                            <td class="px-4 py-3.5 text-right font-bold text-[#1F7A4C] tabular-nums">
                                Rp{{ number_format($perf['total_discount'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-extrabold text-[#1F1A17] tabular-nums">
                                Rp{{ number_format($perf['orders_revenue'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="px-2.5 py-1 rounded-full text-xs font-black {{ $perf['return_rate'] >= 50 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900' }}">
                                    {{ $perf['return_rate'] }}% ({{ $perf['returned_count'] }} Member)
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center font-semibold text-[#3B2A20]">
                                {{ $perf['avg_days_to_return'] !== null ? $perf['avg_days_to_return'] . ' Hari' : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-[#6F6259]">Belum ada data evaluasi promosi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
