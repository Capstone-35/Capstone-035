@extends('layouts.customer', ['title' => 'Pembayaran Pesanan ' . $order->order_number])

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-[#6F6259]">Nomor Transaksi</div>
                <h1 class="text-lg font-extrabold text-[#1F1A17] font-mono">{{ $order->order_number }}</h1>
            </div>
            <x-status-badge :status="$order->status" />
        </div>

        <div class="mt-4 p-4 rounded-xl bg-[#FAF6EF] border border-[#E6DED2] flex items-center justify-between">
            <div>
                <div class="text-xs text-[#6F6259] font-semibold">Total Tagihan</div>
                <div class="text-2xl font-extrabold text-[#3B2A20] tabular-nums">{{ $order->formattedTotal() }}</div>
            </div>
            <div class="text-right">
                <div class="text-xs text-[#6F6259] font-semibold">Kode Penyerahan</div>
                <div class="text-xl font-extrabold font-mono tracking-widest text-[#A9552F]">{{ $order->order_code }}</div>
            </div>
        </div>

        <!-- Method Switcher -->
        <form method="POST" action="{{ route('order.payment.select', ['public_token' => $order->public_token]) }}" class="mt-4">
            @csrf
            <div class="text-xs font-bold text-[#3B2A20] mb-2">Metode Pembayaran Aktif:</div>
            <div class="grid grid-cols-2 gap-2">
                <button type="submit" name="payment_method" value="qris"
                        class="min-h-[44px] px-4 py-2 rounded-xl text-xs font-extrabold border transition {{ $order->payment_method?->value === 'qris' ? 'bg-[#3B2A20] text-[#FAF6EF] border-[#3B2A20]' : 'bg-white text-[#3B2A20] border-[#D6CBBC] hover:bg-[#F2ECE1]' }}">
                    QRIS Digital
                </button>
                <button type="submit" name="payment_method" value="tunai"
                        class="min-h-[44px] px-4 py-2 rounded-xl text-xs font-extrabold border transition {{ $order->payment_method?->value === 'tunai' ? 'bg-[#3B2A20] text-[#FAF6EF] border-[#3B2A20]' : 'bg-white text-[#3B2A20] border-[#D6CBBC] hover:bg-[#F2ECE1]' }}">
                    Tunai di Kasir
                </button>
            </div>
        </form>
    </div>

    <!-- Payment Details Box -->
    @if($order->payment_method?->value === 'qris')
        <div class="bg-white rounded-2xl border border-[#E6DED2] p-6 shadow-sm text-center">
            <span class="inline-block px-3 py-1 rounded-full bg-[#DBEAFE] text-[#1E40AF] text-xs font-extrabold">
                Pembayaran QRIS — 774 HUB
            </span>

            @if($latestPayment && $latestPayment->status->value === 'expired')
                <div class="mt-4 p-4 rounded-xl bg-[#FEE2E2] text-[#991B1B] text-sm font-bold">
                    Waktu pembayaran QRIS (15 menit) telah habis. Silakan buat kode QRIS baru di bawah ini.
                </div>
                <form method="POST" action="{{ route('order.payment.select', ['public_token' => $order->public_token]) }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="payment_method" value="qris">
                    <button type="submit" class="min-h-[44px] px-5 py-2.5 rounded-xl bg-[#3B2A20] text-white text-xs font-bold">
                        Buat Ulang Kode QRIS
                    </button>
                </form>
            @elseif($latestPayment && $latestPayment->status->value === 'failed')
                <div class="mt-4 p-4 rounded-xl bg-[#FEE2E2] text-[#991B1B] text-sm font-bold">
                    Pembayaran QRIS sebelumnya belum berhasil ({{ $latestPayment->failure_reason }}). Silakan coba kembali.
                </div>
            @endif

            @if($qrisSvg)
                <div class="my-5 mx-auto w-56 h-56 p-3 bg-white rounded-2xl border-2 border-[#3B2A20] flex items-center justify-center">
                    {!! $qrisSvg !!}
                </div>
                <div class="text-xs text-[#6F6259]">
                    Referensi: <span class="font-mono font-bold text-[#1F1A17]">{{ $latestPayment->provider_reference }}</span>
                </div>
                @if($latestPayment->expires_at)
                    <div class="mt-1 text-xs font-bold text-[#A9552F]">
                        Berlaku hingga: {{ $latestPayment->expires_at->timezone('Asia/Jakarta')->format('H:i:s') }} WIB (15 menit)
                    </div>
                @endif
            @endif

            @if(!app()->environment('production') && config('app.payment_driver') === 'mock' && $latestPayment && $latestPayment->status->value === 'pending')
                <div class="mt-6 pt-5 border-t border-dashed border-amber-400 bg-amber-50/70 rounded-xl p-4 text-left">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-0.5 rounded bg-amber-600 text-white text-[10px] font-extrabold uppercase">SIMULASI</span>
                        <span class="text-xs font-extrabold text-amber-950">Simulator Callback Webhook QRIS</span>
                    </div>
                    <p class="text-xs text-amber-900 mb-3">
                        Klik tombol di bawah untuk mengirim callback webhook QRIS bertanda tangan HMAC secara nyata ke server:
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('order.payment.simulate_qris', ['public_token' => $order->public_token]) }}">
                            @csrf
                            <input type="hidden" name="scenario" value="paid">
                            <button type="submit" class="min-h-[40px] px-4 py-2 rounded-xl bg-[#1F7A4C] text-white text-xs font-extrabold hover:bg-emerald-800 transition">
                                Simulasikan Pembayaran QRIS Berhasil (Dibayar)
                            </button>
                        </form>
                        <form method="POST" action="{{ route('order.payment.simulate_qris', ['public_token' => $order->public_token]) }}">
                            @csrf
                            <input type="hidden" name="scenario" value="failed">
                            <button type="submit" class="min-h-[40px] px-3.5 py-2 rounded-xl bg-red-700 text-white text-xs font-bold">
                                Simulasikan Gagal
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="bg-white rounded-2xl border border-[#E6DED2] p-6 shadow-sm">
            <span class="inline-block px-3 py-1 rounded-full bg-[#FEF3C7] text-[#92400E] text-xs font-extrabold">
                Instruksi Pembayaran Tunai di Kasir
            </span>
            <h2 class="mt-3 text-base font-extrabold text-[#1F1A17]">Silakan Menuju Meja Kasir 774 HUB</h2>
            <p class="mt-1 text-sm text-[#6F6259]">
                Sebutkan nomor transaksi <strong>{{ $order->order_number }}</strong> atau kode <strong>{{ $order->order_code }}</strong> kepada Kasir dan lakukan pembayaran sebesar <strong>{{ $order->formattedTotal() }}</strong>.
            </p>
            <div class="mt-4 p-3.5 rounded-xl bg-[#FAF6EF] border border-[#E6DED2] text-xs text-[#6F6259]">
                Halaman ini akan otomatis beralih ke pelacakan pesanan begitu Kasir mengonfirmasi pembayaran Anda.
            </div>
        </div>
    @endif

    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('order.track', ['public_token' => $order->public_token]) }}"
           class="min-h-[44px] px-4 py-2.5 inline-flex items-center rounded-xl bg-[#F2ECE1] text-[#3B2A20] text-xs font-bold">
            Lihat Halaman Status Pesanan →
        </a>

        @if($order->status->value === 'Menunggu Pembayaran')
            <form method="POST" action="{{ route('order.cancel.customer', ['public_token' => $order->public_token]) }}"
                  onsubmit="return confirm('Batalkan pesanan ini?');">
                @csrf
                <button type="submit" class="min-h-[44px] px-4 py-2.5 rounded-xl text-xs font-bold text-[#B91C1C] hover:bg-[#FEE2E2]">
                    Batalkan Pesanan
                </button>
            </form>
        @endif
    </div>
</div>

<script>
    setInterval(async () => {
        try {
            const res = await fetch("{{ route('order.status.json', ['public_token' => $order->public_token]) }}", {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.is_paid && data.track_url) {
                window.location.href = data.track_url;
            }
        } catch (e) {}
    }, 4000);
</script>
@endsection
