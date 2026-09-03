{{-- B2 · Text + Media (section-library §3) — flagship editorial layout:
     copy side + media (single) + caption, flip flag. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $mediaSide = ($data['media_side'] ?? 'right') === 'left';
    $media = $data['media_id'] ?? null ? \App\Models\Media::query()->find($data['media_id']) : null;
    $copy = \App\Support\Cms\HtmlSanitizer::clean($data['copy'] ?? '');
@endphp

<section {{ $attributes }}>
    <div data-theme="light" class="px-4 py-12 md:px-6 md:py-16">
        <div class="container mx-auto grid items-center gap-8 md:grid-cols-2 md:gap-12">
            <div class="{{ $mediaSide ? 'md:order-2' : '' }}">
                <h2 class="font-display text-3xl md:text-4xl">{{ $data['title'] ?? '' }}</h2>
                <div class="mt-4 [&_p]:mt-3 [&_p]:leading-relaxed [&_p]:text-ink-soft [&_strong]:text-ink [&_ul]:mt-3 [&_ul]:list-disc [&_ul]:ps-6 [&_li]:mt-1 [&_li]:text-ink-soft [&_a]:font-medium [&_a]:text-brand [&_a]:underline [&_a]:underline-offset-2">
                    {!! $copy !!}
                </div>
            </div>

            <div class="{{ $mediaSide ? 'md:order-1' : '' }}">
                @if ($media)
                    <figure class="m-0">
                        <x-media :media="$media" conversion="card" class="overflow-hidden rounded-2xl" />
                        @if ($data['caption'] ?? null)
                            <figcaption class="mt-2 text-xs text-ink-muted">{{ $data['caption'] }}</figcaption>
                        @endif
                    </figure>
                @else
                    <div class="aspect-[3/2] rounded-2xl bg-paper-3" aria-hidden="true"></div>
                    @if ($data['caption'] ?? null)
                        <p class="mt-2 text-xs text-ink-muted">{{ $data['caption'] }}</p>
                    @endif
                @endif
            </div>
        </div>
    </div>
</section>
