{{--
    Anonymous component: <x-media> (09-media-pipeline §6).
    NOTE on placement: Laravel resolves the anonymous tag <x-media> from
    components/media.blade.php OR components/media/index.blade.php — this
    index view keeps the media/ directory layout while registering the
    exact contract tag (components/media/media.blade.php would register
    as <x-media.media> instead).

    Renders a <picture> with explicit width/height (zero CLS), enforced
    alt discipline (empty string when decorative — never a fallback
    sentence), optional AVIF source for heroes, lazy loading by default
    and `eager` for above-fold heroes. URLs route through MediaUrl so
    production serves the immutable media host (ADR-004).
--}}
@props([
    'media',
    'conversion' => 'card',
    'loading' => 'lazy',
    'class' => '',
    'sizes' => null,
    'eager' => false,
])

@php
    $dims = ['thumb'=>[150,150],'card'=>[600,400],'hero'=>[1600,900],'wide'=>[1920,1080],'og'=>[1200,630],'pdf-cover'=>[600,800]];
@endphp

@if($media)
    <figure class="m-0">
        <picture>
            @if($conversion === 'hero')
                <source type="image/avif" srcset="{{ \App\Support\Media\MediaUrl::to($media->getUrl('hero-avif')) }}">
            @endif
            <img src="{{ \App\Support\Media\MediaUrl::to($media->getUrl($conversion)) }}"
                 alt="{{ $media->is_decorative ? '' : $media->alt_text }}"
                 width="{{ $dims[$conversion][0] }}"
                 height="{{ $dims[$conversion][1] }}"
                 loading="{{ $eager ? 'eager' : $loading }}"
                 decoding="async"
                 @if($sizes) sizes="{{ $sizes }}" @endif
                 class="block h-auto w-full {{ $class }}">
        </picture>
        @if($media->credit)
            <figcaption class="text-ink-muted text-xs mt-1">{{ $media->credit }}</figcaption>
        @endif
    </figure>
@endif
