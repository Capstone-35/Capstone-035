@props(['title', 'description' => null, 'actionUrl' => null, 'actionLabel' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-[#D6CBBC] bg-[#FAF6EF] p-6 sm:p-8 text-center']) }}>
    <div class="mx-auto w-12 h-12 rounded-full bg-[#F2ECE1] flex items-center justify-center text-[#A9552F] font-bold text-lg">
        774
    </div>
    <h3 class="mt-3 text-base font-bold text-[#1F1A17]">{{ $title }}</h3>
    @if($description)
        <p class="mt-1 text-sm text-[#6F6259] max-w-md mx-auto">{{ $description }}</p>
    @endif
    @if($actionUrl && $actionLabel)
        <div class="mt-4">
            <a href="{{ $actionUrl }}" class="inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-sm font-semibold hover:bg-[#2A1E17] transition">
                {{ $actionLabel }}
            </a>
        </div>
    @endif
</div>
