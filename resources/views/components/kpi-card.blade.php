@props(['label', 'value', 'subtext' => null, 'badge' => null, 'accent' => false])

<div {{ $attributes->merge([
    'class' => $accent
        ? 'rounded-2xl p-4 sm:p-5 bg-[#3B2A20] text-[#FAF6EF] shadow-sm border border-[#3B2A20] min-w-0'
        : 'rounded-2xl p-4 sm:p-5 bg-white text-[#1F1A17] shadow-sm border border-[#E6DED2] min-w-0'
]) }}>
    <div class="flex items-start justify-between gap-2">
        <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider leading-snug {{ $accent ? 'text-[#FAF6EF]/80' : 'text-[#6F6259]' }}">
            {{ $label }}
        </span>
        @if($badge)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] sm:text-[11px] font-semibold whitespace-nowrap shrink-0 {{ $accent ? 'bg-[#A9552F] text-white' : 'bg-[#F2ECE1] text-[#3B2A20]' }}">
                {{ $badge }}
            </span>
        @endif
    </div>
    <div class="mt-2 text-xl sm:text-2xl font-bold tabular-nums tracking-tight break-words">
        {{ $value }}
    </div>
    @if($subtext)
        <p class="mt-1 text-xs leading-snug {{ $accent ? 'text-[#FAF6EF]/75' : 'text-[#6F6259]' }}">
            {{ $subtext }}
        </p>
    @endif
</div>
