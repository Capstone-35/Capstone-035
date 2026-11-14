@extends('layouts.staff', ['title' => 'Monitoring Pengiriman Invoice'])

@section('heading', 'Kasir POS — Monitoring & Kirim Ulang Invoice')

@section('content')
<div class="space-y-4">
    <!-- Filter -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2">
            <a href="{{ route('pos.invoices.index') }}"
               class="px-3 py-1.5 rounded-xl font-bold {{ empty($statusFilter) ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Semua Invoice
            </a>
            <a href="{{ route('pos.invoices.index', ['delivery_status' => 'sent']) }}"
               class="px-3 py-1.5 rounded-xl font-bold {{ $statusFilter === 'sent' ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Terkirim (Sent)
            </a>
            <a href="{{ route('pos.invoices.index', ['delivery_status' => 'failed']) }}"
               class="px-3 py-1.5 rounded-xl font-bold {{ $statusFilter === 'failed' ? 'bg-red-700 text-white' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Gagal Kirim (Perlu Resend)
            </a>
        </div>

        <form method="GET" action="{{ route('pos.invoices.index') }}" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ $search }}" placeholder="No. Invoice / Email..."
                   class="px-3.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs">
            <button type="submit" class="px-3 py-1.5 rounded-xl bg-[#3B2A20] text-[#FAF6EF] font-bold">
                Cari
            </button>
        </form>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3">No. Invoice</th>
                        <th class="px-4 py-3">No. Transaksi</th>
                        <th class="px-4 py-3">Pelanggan</th>
                        <th class="px-4 py-3">Penerima & Channel</th>
                        <th class="px-4 py-3">Status Pengiriman</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-center">Aksi Resend</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($invoices as $inv)
                        @php
                            $latestDelivery = $inv->latestDelivery;
                        @endphp
                        <tr class="hover:bg-[#FAF6EF]/60 transition">
                            <td class="px-4 py-3.5 font-mono font-bold text-[#1F1A17]">{{ $inv->invoice_number }}</td>
                            <td class="px-4 py-3.5 font-mono text-[#6F6259]">{{ $inv->order?->order_number }}</td>
                            <td class="px-4 py-3.5 font-bold text-[#1F1A17]">{{ $inv->order?->customer_name }}</td>
                            <td class="px-4 py-3.5">
                                <div>{{ $latestDelivery?->recipient ?? ($inv->order?->customer_email ?: '—') }}</div>
                                <span class="text-[10px] uppercase font-bold text-[#A9552F]">
                                    Channel: {{ $latestDelivery?->channel ?? 'email' }} (Percobaan #{{ $latestDelivery?->attempt_no ?? 1 }})
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                @if($latestDelivery)
                                    <x-status-badge :status="$latestDelivery->status->value" />
                                    @if($latestDelivery->status->value === 'failed' && $latestDelivery->error_message)
                                        <div class="text-[10px] text-red-600 mt-0.5 truncate max-w-xs">{{ $latestDelivery->error_message }}</div>
                                    @endif
                                @else
                                    <span class="text-gray-400">Belum diproses</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right font-extrabold text-[#1F1A17] tabular-nums">
                                {{ $inv->order?->formattedTotal() }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <form method="POST" action="{{ route('pos.invoices.resend', ['invoice' => $inv->id]) }}" class="inline-flex items-center gap-1">
                                    @csrf
                                    <input type="hidden" name="channel" value="email">
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#3B2A20] text-[#FAF6EF] font-bold hover:bg-[#2A1E17]">
                                        Kirim Ulang
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-[#6F6259]">
                                Belum ada data invoice yang sesuai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-[#E6DED2]">
            {{ $invoices->links() }}
        </div>
    </div>
</div>
@endsection
