{{-- <x-button> — the full state machine (ui-components doc): variants
     primary teal / secondary outline / ghost / danger; hover, focus-visible,
     disabled, loading. 44px minimum touch target. Token-driven, RTL-safe. --}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'loading' => false,
    'disabled' => false,
    'href' => null,
])

@php
    $base = 'inline-flex min-h-[44px] items-center justify-center gap-2 rounded-full px-6 text-sm font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:cursor-not-allowed disabled:opacity-50';
    $styles = [
        'primary' => 'bg-brand text-brand-ink hover:opacity-90 active:opacity-80',
        'secondary' => 'border border-line bg-paper text-ink hover:bg-paper-3 active:bg-paper-2',
        'ghost' => 'text-ink hover:bg-paper-3 active:bg-paper-2',
        'danger' => 'bg-danger-500 text-paper hover:opacity-90',
    ];
    $sizes = ['sm' => 'px-4 min-h-[38px]', 'md' => '', 'lg' => 'px-8 text-base'];
    $classes = $base.' '.($styles[$variant] ?? $styles['primary']).' '.($sizes[$size] ?? '').' '.$attributes->get('class', '');
@endphp

@if ($href)
    <a href="{{ $href }}"
       @if (str_starts_with((string) $href, 'http') && ! str_contains((string) $href, config('app.url', ''))) target="_blank" rel="noopener" @endif
       {{ $attributes->class($classes) }}
       @if ($disabled) aria-disabled="true" @endif
       @if ($loading) aria-busy="true" @endif>
        @if ($loading) <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true"></span> @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}"
            {{ $attributes->class($classes) }}
            @if ($disabled) disabled @endif
            @if ($loading) aria-busy="true" @endif>
        @if ($loading) <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true"></span> @endif
        {{ $slot }}
    </button>
@endif
