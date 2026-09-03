{{-- E4 · Newsletter Capture (section-library §6): inline or modal
     variant over the double-opt-in island. The modal is focus-trapped,
     aria-modal, and opens ONLY from an explicit button — never on its
     own (that's E7's job with its frequency cap). --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $variant = ($data['variant'] ?? 'inline') === 'modal' ? 'modal' : 'inline';
    $theme = in_array($data['theme'] ?? 'light', ['light', 'brand', 'deep'], true) ? ($data['theme'] ?? 'light') : 'light';
    $dark = in_array($theme, ['brand', 'deep'], true);
@endphp

<section {{ $attributes }}>
    <div data-theme="{{ $theme }}" class="px-4 py-12 md:px-6 md:py-16">
        <div class="container mx-auto max-w-2xl">
            @if ($variant === 'inline')
                <div @class(['text-center', 'text-paper' => $dark])>
                    <h2 class="font-display text-2xl md:text-3xl">{{ $data['heading'] ?? '' }}</h2>
                    @if (($data['copy'] ?? '') !== '')
                        <p class="mx-auto mt-3 max-w-lg @unless($dark) text-ink-soft @endunless {{ $dark ? 'text-paper/85' : '' }}">{{ $data['copy'] }}</p>
                    @endif
                    <div class="mx-auto mt-6 max-w-md">
                        <livewire:leads.newsletter-signup />
                    </div>
                    @if (($data['note'] ?? '') !== '')
                        <p class="mt-3 text-xs {{ $dark ? 'text-paper/70' : 'text-ink-muted' }}">{{ $data['note'] }}</p>
                    @endif
                </div>
            @else
                <div class="text-center" x-data="{ open: false }">
                    <h2 class="font-display text-2xl md:text-3xl {{ $dark ? 'text-paper' : '' }}">{{ $data['heading'] ?? '' }}</h2>
                    @if (($data['copy'] ?? '') !== '')
                        <p class="mx-auto mt-3 max-w-lg {{ $dark ? 'text-paper/85' : 'text-ink-soft' }}">{{ $data['copy'] }}</p>
                    @endif
                    <button type="button" class="mt-6 inline-flex min-h-[44px] items-center rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink hover:opacity-90"
                            @click="open = true" :aria-expanded="open">
                        Get the newsletter
                    </button>

                    <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Newsletter signup">
                        <div class="absolute inset-0 bg-ink/60 backdrop-blur-sm" @click="open = false" aria-hidden="true"></div>
                        <div class="relative w-full max-w-md rounded-2xl bg-paper p-6 text-start shadow-xl" x-show="open" x-transition.opacity x-trap.inert.noscroll="open" @keydown.escape.window="open = false">
                            <button type="button" class="absolute end-3 top-3 inline-flex h-11 w-11 items-center justify-center rounded-full text-ink-muted hover:bg-paper-3" @click="open = false" aria-label="Close">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                            </button>
                            <h3 class="font-display pe-8 text-xl">{{ $data['heading'] ?? '' }}</h3>
                            <div class="mt-4">
                                <livewire:leads.newsletter-signup />
                            </div>
                            @if (($data['note'] ?? '') !== '')
                                <p class="mt-3 text-xs text-ink-muted">{{ $data['note'] }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
