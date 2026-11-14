@extends('layouts.staff', ['title' => 'Simulator QRIS'])

@section('heading', 'Manager — Simulator Webhook Pembayaran QRIS (Non-Produksi)')

@section('content')
<div class="space-y-6">
    <div class="p-4 rounded-2xl bg-amber-50 border border-amber-300 text-xs text-amber-950 space-y-1">
        <div class="font-extrabold text-sm flex items-center gap-2">
            <span class="px-2 py-0.5 rounded bg-amber-600 text-white text-[10px] uppercase">SIMULASI</span>
            Simulator Callback Webhook QRIS (NFR-06 Idempotent Payment Testing)
        </div>
        <p>
            Modul ini memungkinkan Manager menguji perilaku sistem saat menerima notifikasi pembayaran QRIS dari provider gateway:
            skenario <strong>Berhasil (Paid)</strong>, <strong>Gagal (Failed)</strong>, <strong>Kedaluwarsa (Expired)</strong>,
            <strong>Notifikasi Ganda Replay (Duplicate Idempotent)</strong>, <strong>Nominal Tidak Sesuai (Amount Mismatch)</strong>,
            hingga <strong>HMAC Signature Tidak Valid</strong>.
        </p>
    </div>

    <!-- Active QRIS Payments -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-4">
        <h3 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Transaksi QRIS Terdaftar</h3>

        @if($qrisPayments->isEmpty())
            <p class="text-xs text-[#6F6259] py-4 text-center">Belum ada transaksi dengan metode QRIS yang tercatat.</p>
        @else
            <div class="space-y-3">
                @foreach($qrisPayments as $qPayment)
                    <div class="p-4 rounded-xl border border-[#E6DED2] bg-[#FAF6EF] flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-sm text-[#1F1A17]">{{ $qPayment->order?->order_number }}</span>
                                <x-status-badge :status="$qPayment->status->value" />
                            </div>
                            <div class="font-mono text-[11px] text-[#6F6259] mt-0.5">Ref: {{ $qPayment->provider_reference }}</div>
                            <div class="text-[#3B2A20] font-extrabold text-sm mt-1 tabular-nums">
                                Tagihan: Rp{{ number_format($qPayment->amount, 0, ',', '.') }}
                            </div>
                        </div>

                        <!-- Scenarios form -->
                        <form method="POST" action="{{ route('sandbox.payments.trigger') }}" class="flex flex-wrap items-center gap-2">
                            @csrf
                            <input type="hidden" name="payment_id" value="{{ $qPayment->id }}">

                            <select name="scenario" class="px-2.5 py-1.5 rounded-lg border border-[#D6CBBC] bg-white font-bold text-xs">
                                <option value="paid">1. Sukses Dibayar (Paid)</option>
                                <option value="duplicate">2. Replay Duplicate (Idempotent)</option>
                                <option value="amount_mismatch">3. Nominal Kurang (Amount Mismatch)</option>
                                <option value="failed">4. Pembayaran Gagal (Failed)</option>
                                <option value="expired">5. QRIS Kedaluwarsa (Expired)</option>
                                <option value="invalid_signature">6. Bad Signature (Reject 401)</option>
                            </select>

                            <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-[#3B2A20] text-white font-bold hover:bg-[#2A1E17]">
                                Tembak Webhook
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Webhook Inbound Event Log -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E6DED2]">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">Log Kejadian Webhook Masuk (Tabel payment_webhook_events)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3">Event ID</th>
                        <th class="px-4 py-3">Provider</th>
                        <th class="px-4 py-3">Waktu Masuk</th>
                        <th class="px-4 py-3 text-center">HMAC Valid</th>
                        <th class="px-4 py-3 text-center">Hasil Pemrosesan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($webhookLogs as $log)
                        <tr class="hover:bg-[#FAF6EF]/60">
                            <td class="px-4 py-3 font-mono font-bold">{{ $log->event_id }}</td>
                            <td class="px-4 py-3 font-semibold uppercase text-[#6F6259]">{{ $log->provider }}</td>
                            <td class="px-4 py-3 text-[#6F6259]">{{ $log->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $log->signature_valid ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $log->signature_valid ? 'VALID' : 'INVALID' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded font-mono font-bold text-[11px] bg-gray-100 text-[#1F1A17]">
                                    {{ $log->result }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-[#6F6259]">Belum ada payload webhook yang diterima.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
