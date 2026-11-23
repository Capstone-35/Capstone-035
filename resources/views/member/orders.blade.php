@extends('layouts.customer', ['title' => 'Riwayat Transaksi Member'])

@section('content')
<div class="max-w-5xl mx-auto space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-lg sm:text-xl font-extrabold text-[#1F1A17]">Riwayat Transaksi Saya</h1>
        <a href="{{ route('member.dashboard') }}" class="min-h-[40px] inline-flex items-center text-xs font-bold text-[#6F6259] hover:text-[#3B2A20]">
            ← Portal Member
        </a>
    </div>

    <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar">
        <a href="{{ route('member.orders.index') }}"
           class="min-h-[42px] inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap shrink-0 {{ empty($statusFilter) ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-white border border-[#E6DED2] text-[#6F6259]' }}">
            Semua Status
        </a>
        @foreach(\App\Enums\OrderStatus::cases() as $st)
            <a href="{{ route('member.orders.index', ['status' => $st->value]) }}"
               class="min-h-[42px] inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap shrink-0 {{ $statusFilter === $st->value ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-white border border-[#E6DED2] text-[#6F6259]' }}">
                {{ $st->value }}
            </a>
        @endforeach
    </div>

    @if($orders->isEmpty())
        <x-empty-state
            title="Belum Ada Transaksi"
            description="Tidak ditemukan transaksi Member untuk filter status ini."
            :action-url="route('menu.index')"
            action-label="Pesan Menu Sekarang"
        />
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
            @foreach($orders as $order)
                <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm flex flex-col justify-between space-y-3">
                    <div class="space-y-2.5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="min-w-0">
                                <a href="{{ route('member.orders.show', ['order' => $order->order_number]) }}" class="font-mono text-sm font-extrabold text-[#1F1A17] hover:underline break-all">
                                    {{ $order->order_number }}
                                </a>
                                <div class="text-xs text-[#6F6259]">
                                    {{ $order->created_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB • {{ $order->table?->label ?? 'POS' }}
                                </div>
                            </div>
                            <x-status-badge :status="$order->status" />
                        </div>

                        <div class="text-xs text-[#6F6259]">
                            @foreach($order->items as $item)
                                <span class="inline-block mr-2">{{ $item->quantity }}x {{ $item->product_name }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-3 border-t border-[#F2ECE1] flex flex-wrap items-center justify-between gap-2">
                        <div class="text-sm font-extrabold text-[#3B2A20] tabular-nums whitespace-nowrap">
                            Total: {{ $order->formattedTotal() }}
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <form method="POST" action="{{ route('member.orders.repeat', ['order' => $order->order_number]) }}" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                                @csrf
                                <button type="submit" class="min-h-[42px] px-3.5 py-1.5 rounded-xl bg-[#A9552F] text-white text-xs font-bold hover:bg-[#8E4423] transition">
                                    Pesan Lagi
                                </button>
                            </form>
                            <a href="{{ route('member.orders.show', ['order' => $order->order_number]) }}"
                               class="min-h-[42px] px-3.5 py-1.5 inline-flex items-center rounded-xl border border-[#D6CBBC] text-xs font-bold text-[#3B2A20] hover:bg-[#F2ECE1] transition">
                                Detail
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection
