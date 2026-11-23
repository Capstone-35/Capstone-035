@extends('layouts.kds')

@section('content')
@php
    $initialFingerprint = collect()
        ->merge($paidOrders->map(fn ($o) => $o->order_number . ':' . $o->status->value))
        ->merge($processingOrders->map(fn ($o) => $o->order_number . ':' . $o->status->value))
        ->merge($readyOrders->map(fn ($o) => $o->order_number . ':' . $o->status->value))
        ->sort()
        ->values()
        ->implode('|');
@endphp

<!-- Top Status Counts -->
<div class="mb-4 grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
    <div class="p-3.5 rounded-2xl bg-blue-950/80 border border-blue-500/50 flex items-center justify-between gap-2 min-w-0">
        <div class="min-w-0">
            <div class="text-xs uppercase font-extrabold text-blue-300 truncate">1. Pesanan Baru (Dibayar)</div>
            <div class="text-2xl font-black text-white tabular-nums mt-0.5" id="count-dibayar">{{ $paidOrders->count() }}</div>
        </div>
        <span class="w-3 h-3 rounded-full bg-blue-400 shrink-0"></span>
    </div>
    <div class="p-3.5 rounded-2xl bg-indigo-950/80 border border-indigo-500/50 flex items-center justify-between gap-2 min-w-0">
        <div class="min-w-0">
            <div class="text-xs uppercase font-extrabold text-indigo-300 truncate">2. Sedang Diproses</div>
            <div class="text-2xl font-black text-white tabular-nums mt-0.5" id="count-diproses">{{ $processingOrders->count() }}</div>
        </div>
        <span class="w-3 h-3 rounded-full bg-indigo-400 animate-ping shrink-0"></span>
    </div>
    <div class="p-3.5 rounded-2xl bg-teal-950/80 border border-teal-500/50 flex items-center justify-between gap-2 min-w-0">
        <div class="min-w-0">
            <div class="text-xs uppercase font-extrabold text-teal-300 truncate">3. Siap Diserahkan</div>
            <div class="text-2xl font-black text-white tabular-nums mt-0.5" id="count-siap">{{ $readyOrders->count() }}</div>
        </div>
        <span class="w-3 h-3 rounded-full bg-teal-400 shrink-0"></span>
    </div>
</div>

<!-- 3-Column KDS Board (1 col Mobile/iPad Portrait, 3 cols iPad Landscape/Desktop) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:h-[calc(100vh-14.5rem)]">
    <!-- Column 1: Dibayar (Pesanan Baru) -->
    <div class="bg-[#261E1A] rounded-2xl border border-white/10 p-3.5 sm:p-4 flex flex-col min-h-[260px] lg:min-h-0">
        <h2 class="text-sm font-extrabold text-blue-300 uppercase tracking-wider mb-3 flex items-center justify-between gap-2 shrink-0">
            <span>Pesanan Baru</span>
            <span class="text-xs font-mono font-bold text-white/70">Dibayar</span>
        </h2>

        <div class="space-y-3 flex-1 overflow-y-auto pr-0.5">
            @forelse($paidOrders as $order)
                <div class="p-3.5 sm:p-4 rounded-xl bg-[#352A24] border border-blue-400/40 shadow-sm space-y-3">
                    <div class="flex items-start justify-between gap-2 border-b border-white/10 pb-2">
                        <div class="min-w-0 break-words">
                            <div class="font-mono text-base sm:text-lg font-black text-white">{{ $order->shortNumber() }}</div>
                            <div class="text-xs text-white/70">{{ $order->table?->label ?? 'Takeaway' }} • <strong>{{ $order->customer_name }}</strong></div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="px-2.5 py-1 rounded-lg bg-[#A9552F] text-white font-mono font-black text-xs tracking-wider inline-block">
                                {{ $order->order_code }}
                            </span>
                            <div class="text-[11px] text-white/60 font-mono mt-1">{{ $order->paid_at?->timezone('Asia/Jakarta')->format('H:i:s') }}</div>
                        </div>
                    </div>

                    <div class="space-y-1.5 text-xs text-[#FAF6EF]">
                        @foreach($order->items as $it)
                            <div class="flex items-start justify-between font-bold">
                                <span>{{ $it->quantity }}x {{ $it->product_name }}</span>
                            </div>
                            @if($it->note)
                                <div class="text-[11px] text-amber-300 bg-black/30 px-2 py-0.5 rounded font-medium break-words">Catatan: "{{ $it->note }}"</div>
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
    <div class="bg-[#261E1A] rounded-2xl border border-white/10 p-3.5 sm:p-4 flex flex-col min-h-[260px] lg:min-h-0">
        <h2 class="text-sm font-extrabold text-indigo-300 uppercase tracking-wider mb-3 flex items-center justify-between gap-2 shrink-0">
            <span>Sedang Dikerjakan</span>
            <span class="text-xs font-mono font-bold text-white/70">Diproses</span>
        </h2>

        <div class="space-y-3 flex-1 overflow-y-auto pr-0.5">
            @forelse($processingOrders as $order)
                <div class="p-3.5 sm:p-4 rounded-xl bg-[#352A24] border border-indigo-400/50 shadow-sm space-y-3">
                    <div class="flex items-start justify-between gap-2 border-b border-white/10 pb-2">
                        <div class="min-w-0 break-words">
                            <div class="font-mono text-base sm:text-lg font-black text-white">{{ $order->shortNumber() }}</div>
                            <div class="text-xs text-white/70">{{ $order->table?->label ?? 'Takeaway' }} • <strong>{{ $order->customer_name }}</strong></div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="px-2.5 py-1 rounded-lg bg-[#A9552F] text-white font-mono font-black text-xs tracking-wider inline-block">
                                {{ $order->order_code }}
                            </span>
                            <div class="text-[11px] text-indigo-300 font-mono mt-1">Mulai: {{ $order->processing_at?->timezone('Asia/Jakarta')->format('H:i:s') }}</div>
                        </div>
                    </div>

                    <div class="space-y-1.5 text-xs text-[#FAF6EF]">
                        @foreach($order->items as $it)
                            <div class="flex items-start justify-between font-bold">
                                <span>{{ $it->quantity }}x {{ $it->product_name }}</span>
                            </div>
                            @if($it->note)
                                <div class="text-[11px] text-amber-300 bg-black/30 px-2 py-0.5 rounded font-medium break-words">Catatan: "{{ $it->note }}"</div>
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
    <div class="bg-[#261E1A] rounded-2xl border border-white/10 p-3.5 sm:p-4 flex flex-col min-h-[260px] lg:min-h-0">
        <h2 class="text-sm font-extrabold text-teal-300 uppercase tracking-wider mb-3 flex items-center justify-between gap-2 shrink-0">
            <span>Siap Diserahkan</span>
            <span class="text-xs font-mono font-bold text-white/70">Siap</span>
        </h2>

        <div class="space-y-3 flex-1 overflow-y-auto pr-0.5">
            @forelse($readyOrders as $order)
                <div class="p-3.5 sm:p-4 rounded-xl bg-[#352A24] border border-teal-400/50 shadow-sm space-y-3">
                    <div class="flex items-start justify-between gap-2 border-b border-white/10 pb-2">
                        <div class="min-w-0 break-words">
                            <div class="font-mono text-base sm:text-lg font-black text-white">{{ $order->shortNumber() }}</div>
                            <div class="text-xs text-white/70">{{ $order->table?->label ?? 'Takeaway' }} • <strong>{{ $order->customer_name }}</strong></div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="px-2.5 py-1 rounded-lg bg-[#A9552F] text-white font-mono font-black text-xs tracking-wider inline-block">
                                {{ $order->order_code }}
                            </span>
                            <div class="text-[11px] text-teal-300 font-mono mt-1">Siap: {{ $order->ready_at?->timezone('Asia/Jakarta')->format('H:i:s') }}</div>
                        </div>
                    </div>

                    <div class="space-y-1 text-xs text-white/85">
                        @foreach($order->items as $it)
                            <div class="font-semibold">{{ $it->quantity }}x {{ $it->product_name }}</div>
                            @if($it->note)
                                <div class="text-[11px] text-amber-300 bg-black/30 px-2 py-0.5 rounded font-medium break-words">Catatan: "{{ $it->note }}"</div>
                            @endif
                        @endforeach
                    </div>

                    <!-- Handover from KDS or Barista -->
                    <form method="POST" action="{{ route('kds.orders.handover', ['order' => $order->order_number]) }}" class="pt-2 flex flex-wrap items-center gap-2">
                        @csrf
                        <input type="text" name="order_code" placeholder="Kode 4 Char" maxlength="8" required
                               class="w-28 flex-1 sm:flex-none min-w-0 min-h-[44px] px-2.5 py-1 rounded-xl bg-black/40 border border-white/20 text-white font-mono font-black text-center text-xs uppercase">
                        <button type="submit" class="flex-1 min-w-[110px] min-h-[44px] px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs uppercase tracking-wider transition">
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
@endsection

@push('scripts')
<script>
    // Live KDS Polling (every 3 seconds - NFR-03 requirement)
    (function () {
        const initialFingerprint = @json($initialFingerprint);
        let inFlight = false;
        let pendingReload = false;

        function setLiveIndicator(online) {
            const box = document.getElementById('kds-live-indicator');
            const dot = document.getElementById('kds-live-dot');
            const text = document.getElementById('kds-live-text');
            if (!box || !dot || !text) return;
            if (online) {
                box.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-950 border border-emerald-600 text-emerald-300 text-xs font-bold whitespace-nowrap';
                dot.className = 'w-2 h-2 rounded-full bg-emerald-400 animate-pulse shrink-0';
                text.textContent = 'Sinkron Aktif';
            } else {
                box.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-red-950 border border-red-500 text-red-200 text-xs font-bold whitespace-nowrap';
                dot.className = 'w-2 h-2 rounded-full bg-red-400 shrink-0';
                text.textContent = 'Koneksi Terputus';
            }
        }

        function isUserTyping() {
            const active = document.activeElement;
            return active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA');
        }

        document.addEventListener('focusout', function () {
            if (pendingReload && !isUserTyping()) {
                window.location.reload();
            }
        });

        setInterval(async () => {
            if (inFlight || document.hidden) return;
            inFlight = true;
            try {
                const res = await fetch("{{ route('kds.feed') }}", {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) {
                    setLiveIndicator(false);
                    return;
                }
                const data = await res.json();
                setLiveIndicator(true);

                if (data.counts) {
                    const el1 = document.getElementById('count-dibayar');
                    const el2 = document.getElementById('count-diproses');
                    const el3 = document.getElementById('count-siap');
                    if (el1) el1.textContent = data.counts.dibayar ?? 0;
                    if (el2) el2.textContent = data.counts.diproses ?? 0;
                    if (el3) el3.textContent = data.counts.siap ?? 0;
                }

                const newFingerprint = (data.orders || [])
                    .map((o) => `${o.order_number}:${o.status}`)
                    .sort()
                    .join('|');

                if (newFingerprint !== initialFingerprint) {
                    if (isUserTyping()) {
                        pendingReload = true;
                    } else {
                        window.location.reload();
                    }
                }
            } catch (e) {
                setLiveIndicator(false);
            } finally {
                inFlight = false;
            }
        }, 3000);
    })();
</script>
@endpush
