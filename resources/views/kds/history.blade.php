@extends('layouts.kds')

@section('content')
<div class="space-y-6">
    <!-- Quick Product Availability Toggle (Barista Kitchen Control) -->
    <div class="bg-[#261E1A] rounded-2xl border border-white/10 p-4 sm:p-5 shadow-sm space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 pb-3">
            <div class="min-w-0">
                <h2 class="text-base font-extrabold text-white">Status Ketersediaan Menu (Quick Toggle)</h2>
                <p class="text-xs text-white/60">Klik tombol untuk mengubah status menu antara Tersedia dan Habis saat bahan baku habis.</p>
            </div>
            <a href="{{ route('kds.index') }}" class="min-h-[42px] inline-flex items-center px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-bold text-white whitespace-nowrap shrink-0 transition">
                ← Kembali ke Papan KDS
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
            @foreach($products as $prod)
                <div class="p-3.5 rounded-xl border {{ $prod->is_available ? 'border-emerald-500/40 bg-emerald-950/20' : 'border-red-500/40 bg-red-950/30' }} flex items-center justify-between gap-2.5 min-w-0">
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-xs text-white truncate">{{ $prod->name }}</div>
                        <div class="text-[10px] text-white/60 truncate">{{ $prod->category?->name }}</div>
                    </div>
                    <form method="POST" action="{{ route('kds.products.toggle', ['product' => $prod->id]) }}" class="shrink-0">
                        @csrf
                        <button type="submit" class="min-h-[38px] px-3 py-1.5 rounded-lg text-[11px] font-extrabold whitespace-nowrap transition {{ $prod->is_available ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-red-600 hover:bg-red-500 text-white' }}">
                            {{ $prod->is_available ? 'Tersedia' : 'Habis' }}
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Completed Orders History -->
    <div class="bg-[#261E1A] rounded-2xl border border-white/10 p-4 sm:p-5 shadow-sm space-y-3">
        <h2 class="text-base font-extrabold text-white border-b border-white/10 pb-2">Riwayat Pesanan Selesai Terakhir</h2>
        <div class="divide-y divide-white/5 text-xs text-white/80">
            @forelse($completedOrders as $cOrder)
                <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 min-w-0">
                        <span class="font-mono font-bold text-white">{{ $cOrder->shortNumber() }}</span>
                        <span class="text-white/65">({{ $cOrder->table?->label ?? 'Takeaway' }} • {{ $cOrder->customer_name }})</span>
                        <span class="font-mono text-[11px] text-emerald-400">Kode: {{ $cOrder->order_code }}</span>
                    </div>
                    <div class="text-left sm:text-right text-[11px] text-white/60 shrink-0">
                        Diserahkan: {{ $cOrder->completed_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }} WIB
                        @if($cOrder->handedOverBy)
                            oleh {{ $cOrder->handedOverBy->name }}
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-xs text-white/40 py-6 text-center">Belum ada riwayat pesanan selesai.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
