{{-- C2 · Carousel (section-library §4) — CSS scroll-snap + arrows +
    dots, keyboard operable; autoplay OFF by default; no Swiper. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $items = is_array($data['items'] ?? null) ? array_values($data['items']) : [];
    $uid = 'car-'.\Illuminate\Support\Str::random(5);
@endphp

<div {{ $attributes }} x-data="{ slide: 0 }">
    <div data-theme="light" class="px-4 py-10 md:px-6 md:py-12">
        <div class="container mx-auto">
            <div class="relative">
                <div id="{{ $uid }}" class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($items as $i => $item)
                        @php($media = ($item['media_id'] ?? null) ? \App\Models\Media::query()->find($item['media_id']) : null)
                        <figure class="m-0 w-[85%] shrink-0 snap-center sm:w-[60%] md:w-[45%]">
                            <div class="aspect-[3/2] overflow-hidden rounded-2xl bg-paper-3">
                                @if ($media)
                                    <x-media :media="$media" conversion="card" class="h-full w-full [&>img]:h-full [&>img]:w-full [&>img]:object-cover" />
                                @endif
                            </div>
                            @if ($item['caption'] ?? null)
                                <figcaption class="mt-2 text-xs text-ink-muted">{{ $item['caption'] }}</figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>

                @if (count($items) > 1)
                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex gap-1.5" role="tablist" aria-label="Slides">
                            @foreach ($items as $i => $item)
                                <button type="button" role="tab" :aria-selected="(slide === {{ $i }}).toString()"
                                        @click="slide = {{ $i }}; document.getElementById('{{ $uid }}').children[{{ $i }}].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' })"
                                        class="h-2.5 w-2.5 rounded-full border-0 p-0 transition-colors"
                                        :class="slide === {{ $i }} ? 'bg-brand' : 'bg-paper-3'"
                                        aria-label="Go to slide {{ $i + 1 }}"></button>
                            @endforeach
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="slide = Math.max(0, slide - 1); document.getElementById('{{ $uid }}').children[slide].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' })"
                                    class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-line text-ink hover:bg-paper-3" aria-label="Previous slide">←</button>
                            <button type="button" @click="slide = Math.min({{ count($items) - 1 }}, slide + 1); document.getElementById('{{ $uid }}').children[slide].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' })"
                                    class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-line text-ink hover:bg-paper-3" aria-label="Next slide">→</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
