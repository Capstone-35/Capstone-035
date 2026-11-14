@extends('layouts.staff', ['title' => 'Monitor Antrean Pesanan'])

@section('heading', 'Manager — Monitor Seluruh Antrean Operasional')

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm flex items-center justify-between">
        <div>
            <h2 class="text-base font-extrabold text-[#1F1A17]">Antrean Pesanan Berjalan (Live Queue)</h2>
            <p class="text-xs text-[#6F6259]">Memantau seluruh transaksi yang masih dalam proses pelayanan.</p>
        </div>
        <span class="px-3 py-1 rounded-full bg-[#3B2A20] text-white font-bold text-xs">
            {{ $activeOrders->count() }} Pesanan Aktif
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($activeOrders as $o)
            <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-2 border-b border-[#F2ECE1] pb-2">
                        <div>
                            <span class="font-mono font-bold text-sm text-[#1F1A17]">{{ $o->order_number }}</span>
                            <div class="text-xs text-[#6F6259]">{{ $o->table?->label ?? 'Takeaway' }} • {{ $o->customer_name }}</div>
                        </div>
                        <x-status-badge :status="$o->status" />
                    </div>

                    <div class="my-3 space-y-1 text-xs text-[#1F1A17]">
                        @foreach($o->items as $it)
                            <div>{{ $it->quantity }}x {{ $it->product_name }}</div>
                        @endforeach
                    </div>
                </div>

                <div class="pt-3 border-t border-[#F2ECE1] flex items-center justify-between">
                    <span class="font-bold text-xs text-[#3B2A20] tabular-nums">{{ $o->formattedTotal() }}</span>
                    <a href="{{ route('manager.transactions.show', ['order' => $o->order_number]) }}"
                       class="px-3 py-1 rounded-lg border border-[#D6CBBC] text-xs font-bold hover:bg-[#F2ECE1]">
                        Rincian Transaksi
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-xs text-[#6F6259]">
                Tidak ada pesanan aktif saat ini.
            </div>
        @endforelse
    </div>
</div>
@endsection
