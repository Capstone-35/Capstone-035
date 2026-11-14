@props(['status'])

@php
    $value = $status instanceof \App\Enums\OrderStatus ? $status->value : (string) $status;

    $classes = match ($value) {
        'Menunggu Pembayaran', 'pending' => 'bg-[#FEF3C7] text-[#92400E] border-[#FCD34D]',
        'Dibayar', 'paid', 'sent' => 'bg-[#DBEAFE] text-[#1E40AF] border-[#93C5FD]',
        'Diproses' => 'bg-[#E0E7FF] text-[#3730A3] border-[#A5B4FC]',
        'Siap' => 'bg-[#CCFBF1] text-[#115E59] border-[#5EEAD4]',
        'Selesai', 'active', 'used' => 'bg-[#DCFCE7] text-[#166534] border-[#86EFAC]',
        'Dibatalkan', 'failed', 'expired', 'cancelled', 'amount_mismatch' => 'bg-[#FEE2E2] text-[#991B1B] border-[#FCA5A5]',
        default => 'bg-[#F2ECE1] text-[#3B2A20] border-[#E6DED2]',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border {$classes}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full bg-current opacity-80"></span>
    {{ $value }}
</span>
