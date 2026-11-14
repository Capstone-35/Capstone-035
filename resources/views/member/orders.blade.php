@extends('layouts.customer', ['title' => 'Riwayat Transaksi Member'])

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-extrabold text-[#1F1A17]">Riwayat Transaksi Saya</h1>
        <a href="{{ route('member.dashboard') }}" class="text-xs font-bold text-[#6F6259] hover:text-[#3B2A20]">
            ← Portal Member
        </a>
    </div>

    <div class="flex items-center gap-2 overflow-x-auto pb-1">
        <a href="{{ route('member.orders.index') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap {{ empty($statusFilter) ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-white border border-[#E6DED2] text-[#6F6259]' }}">
            Semua Status
        </a>
        @foreach(\App\Enums\OrderStatus::cases() as $st)
            <a href="{{ route('member.orders.index', ['status' => $st->value]) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap {{ $statusFilter === $st->value ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-white border border-[#E6DED2] text-[#6F6259]' }}">
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
        <div class="space-y-3">
            @foreach($orders as $order)
                <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <a href="{{ route('member.orders.show', ['order' => $order->order_number]) }}" class="font-mono text-sm font-extrabold text-[#1F1A17] hover:underline">
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

                    <div class="pt-3 border-t border-[#F2ECE1] flex flex-wrap items-center justify-between gap-2">
                        <div class="text-sm font-extrabold text-[#3B2A20] tabular-nums">
                            Total: {{ $order->formattedTotal() }}
                        </div>

                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('member.orders.repeat', ['order' => $order->order_number]) }}">
                                @csrf
                                <button type="submit" class="min-h-[38px] px-3.5 py-1.5 rounded-xl bg-[#A9552F] text-white text-xs font-bold hover:bg-[#8E4423]">
                                    Pesan Lagi
                                </button>
                            </form>
                            <a href="{{ route('member.orders.show', ['order' => $order->order_number]) }}"
                               class="min-h-[38px] px-3.5 py-1.5 inline-flex items-center rounded-xl border border-[#D6CBBC] text-xs font-bold text-[#3B2A20]">
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
