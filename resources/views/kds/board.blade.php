@extends('layouts.kds')

@section('content')
<!-- Top Status Counts -->
<div class="mb-4 grid grid-cols-3 gap-4">
    <div class="p-3.5 rounded-2xl bg-blue-950/80 border border-blue-500/50 flex items-center justify-between">
        <div>
            <div class="text-xs uppercase font-extrabold text-blue-300">1. Pesanan Baru (Dibayar)</div>
            <div class="text-2xl font-black text-white tabular-nums mt-0.5" id="count-dibayar">{{ $paidOrders->count() }}</div>
        </div>
        <span class="w-3 h-3 rounded-full bg-blue-400"></span>
    </div>
    <div class="p-3.5 rounded-2xl bg-indigo-950/80 border border-indigo-500/50 flex items-center justify-between">
        <div>
            <div class="text-xs uppercase font-extrabold text-indigo-300">2. Sedang Diproses</div>
            <div class="text-2xl font-black text-white tabular-nums mt-0.5" id="count-diproses">{{ $processingOrders->count() }}</div>
        </div>
        <span class="w-3 h-3 rounded-full bg-indigo-400 animate-ping"></span>
    </div>
    <div class="p-3.5 rounded-2xl bg-teal-950/80 border border-teal-500/50 flex items-center justify-between">
        <div>
            <div class="text-xs uppercase font-extrabold text-teal-300">3. Siap Diserahkan</div>
            <div class="text-2xl font-black text-white tabular-nums mt-0.5" id="count-siap">{{ $readyOrders->count() }}</div>
        </div>
        <span class="w-3 h-3 rounded-full bg-teal-400"></span>
    </div>
</div>

<!-- 3-Column KDS Board -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 min-h-[70vh]">
    <!-- Column 1: Dibayar (Pesanan Baru) -->
    <div class="bg-[#261E1A] rounded-2xl border border-white/10 p-4 flex flex-col">
        <h2 class="text-sm font-extrabold text-blue-300 uppercase tracking-wider mb-3 flex items-center justify-between">
            <span>Pesanan Baru</span>
            <span class="text-xs font-mono font-bold text-white/70">Dibayar</span>
        </h2>

        <div class="space-y-3 flex-1 overflow-y-auto">
            @forelse($paidOrders as $order)
                <div class="p-4 rounded-xl bg-[#352A24] border border-blue-400/40 shadow-sm space-y-3">
                    <div class="flex items-start justify-between gap-2 border-b border-white/10 pb-2">
                        <div>
                            <div class="font-mono text-lg font-black text-white">{{ $order->shortNumber() }}</div>
                            <div class="text-xs text-white/70">{{ $order->table?->label ?? 'Takeaway' }} • <strong>{{ $order->customer_name }}</strong></div>
                        </div>
                        <div class="text-right">
                            <span class="px-2.5 py-1 rounded-lg bg-[#A9552F] text-white font-mono font-black text-xs tracking-wider">
                                {{ $order->order_code }}
                            </span>
                            <div class="text-[11px] text-white/60 font-mono mt-1">{{ $order->paid_at?->format('H:i:s') }}</div>
                        </div>
                    </div>

                    <div class="space-y-1.5 text-xs text-[#FAF6EF]">
                        @foreach($order->items as $it)
                            <div class="flex items-start justify-between font-bold">
                                <span>{{ $it->quantity }}x {{ $it->product_name }}</span>
                            </div>
                            @if($it->note)
                                <div class="text-[11px] text-amber-300 bg-black/30 px-2 py-0.5 rounded font-medium">Catatan: "{{ $it->note }}"</div>
                            @endif
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('kds.orders.start', ['order' => $order->order_number]) }}" class="pt-2">
                        @csrf
                        <button type="submit" class="w-full min-h-[44px] rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-black text-xs uppercase tracking-wider transition">
                            Mulai Proses Dapur →
                        </button>
                    </form>
                </div>
            @empty
                <p class="text-xs text-white/40 text-center py-10">Tidak ada pesanan baru.</p>
            @endforelse
        </div>
    </div>

    <!-- Column 2: Diproses (Sedang Disiapkan) -->
    <div class="bg-[#261E1A] rounded-2xl border border-white/10 p-4 flex flex-col">
        <h2 class="text-sm font-extrabold text-indigo-300 uppercase tracking-wider mb-3 flex items-center justify-between">
            <span>Sedang Dikerjakan</span>
            <span class="text-xs font-mono font-bold text-white/70">Diproses</span>
        </h2>

        <div class="space-y-3 flex-1 overflow-y-auto">
            @forelse($processingOrders as $order)
                <div class="p-4 rounded-xl bg-[#352A24] border border-indigo-400/50 shadow-sm space-y-3">
                    <div class="flex items-start justify-between gap-2 border-b border-white/10 pb-2">
                        <div>
                            <div class="font-mono text-lg font-black text-white">{{ $order->shortNumber() }}</div>
                            <div class="text-xs text-white/70">{{ $order->table?->label ?? 'Takeaway' }} • <strong>{{ $order->customer_name }}</strong></div>
                        </div>
                        <div class="text-right">
                            <span class="px-2.5 py-1 rounded-lg bg-[#A9552F] text-white font-mono font-black text-xs tracking-wider">
                                {{ $order->order_code }}
                            </span>
                            <div class="text-[11px] text-indigo-300 font-mono mt-1">Mulai: {{ $order->processing_at?->format('H:i:s') }}</div>
                        </div>
                    </div>

                    <div class="space-y-1.5 text-xs text-[#FAF6EF]">
                        @foreach($order->items as $it)
                            <div class="flex items-start justify-between font-bold">
                                <span>{{ $it->quantity }}x {{ $it->product_name }}</span>
                            </div>
                            @if($it->note)
                                <div class="text-[11px] text-amber-300 bg-black/30 px-2 py-0.5 rounded font-medium">Catatan: "{{ $it->note }}"</div>
                            @endif
                        @endforeach
                    </div>

                    <!-- D11 Quality Confirmation Loop: "Pesanan sudah sesuai?" -->
                    <form method="POST" action="{{ route('kds.orders.ready', ['order' => $order->order_number]) }}"
                          onsubmit="return confirm('Pesanan sudah sesuai dengan item dan catatan pesanan?');" class="pt-2">
                        @csrf
                        <input type="hidden" name="quality_confirmed" value="1">
                        <button type="submit" class="w-full min-h-[44px] rounded-xl bg-teal-600 hover:bg-teal-500 text-white font-black text-xs uppercase tracking-wider transition">
                            Tandai Siap (Verifikasi Sesuai) →
                        </button>
                    </form>
                </div>
            @empty
                <p class="text-xs text-white/40 text-center py-10">Tidak ada pesanan sedang diproses.</p>
            @endforelse
        </div>
    </div>

    <!-- Column 3: Siap (Siap Diserahkan) -->
    <div class="bg-[#261E1A] rounded-2xl border border-white/10 p-4 flex flex-col">
        <h2 class="text-sm font-extrabold text-teal-300 uppercase tracking-wider mb-3 flex items-center justify-between">
            <span>Siap Diserahkan</span>
            <span class="text-xs font-mono font-bold text-white/70">Siap</span>
        </h2>

        <div class="space-y-3 flex-1 overflow-y-auto">
            @forelse($readyOrders as $order)
                <div class="p-4 rounded-xl bg-[#352A24] border border-teal-400/50 shadow-sm space-y-3">
                    <div class="flex items-start justify-between gap-2 border-b border-white/10 pb-2">
                        <div>
                            <div class="font-mono text-lg font-black text-white">{{ $order->shortNumber() }}</div>
                            <div class="text-xs text-white/70">{{ $order->table?->label ?? 'Takeaway' }} • <strong>{{ $order->customer_name }}</strong></div>
                        </div>
                        <div class="text-right">
                            <span class="px-2.5 py-1 rounded-lg bg-[#A9552F] text-white font-mono font-black text-xs tracking-wider">
                                {{ $order->order_code }}
                            </span>
                            <div class="text-[11px] text-teal-300 font-mono mt-1">Siap: {{ $order->ready_at?->format('H:i:s') }}</div>
                        </div>
                    </div>

                    <div class="space-y-1 text-xs text-white/80">
                        @foreach($order->items as $it)
                            <div>{{ $it->quantity }}x {{ $it->product_name }}</div>
                        @endforeach
                    </div>

                    <!-- Handover from KDS or Barista -->
                    <form method="POST" action="{{ route('kds.orders.handover', ['order' => $order->order_number]) }}" class="pt-2 flex items-center gap-1.5">
                        @csrf
                        <input type="text" name="order_code" placeholder="4 Karakter" maxlength="8" required
                               class="w-28 min-h-[44px] px-2 py-1 rounded-xl bg-black/40 border border-white/20 text-white font-mono font-black text-center text-xs uppercase">
                        <button type="submit" class="flex-1 min-h-[44px] rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs uppercase tracking-wider">
                            Serahkan
                        </button>
                    </form>
                </div>
            @empty
                <p class="text-xs text-white/40 text-center py-10">Belum ada pesanan yang siap diserahkan.</p>
            @endforelse
        </div>
    </div>
</div>

<script>
    // Live KDS Polling (every 3 seconds - NFR-03 requirement)
    setInterval(async () => {
        try {
            const res = await fetch("{{ route('kds.feed') }}", {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();
            const currentTotal = {{ $paidOrders->count() + $processingOrders->count() + $readyOrders->count() }};
            const newTotal = (data.counts.dibayar || 0) + (data.counts.diproses || 0) + (data.counts.siap || 0);

            if (newTotal !== currentTotal) {
                window.location.reload();
            }
        } catch (e) {}
    }, 3000);
</script>
@endsection
