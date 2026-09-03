{{-- E7 · Exit-Intent Modal (section-library §6): trigger rules
     (exit/scroll/time) + 1-per-7-days frequency cap via localStorage +
     NEVER on first paint. Focus-trapped, esc-closable, aria-modal. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $trigger = in_array($data['trigger'] ?? 'exit', ['exit', 'scroll', 'time'], true) ? ($data['trigger'] ?? 'exit') : 'exit';
    $scrollPct = max(10, min(95, (int) ($data['scroll_pct'] ?? 60)));
    $delaySeconds = max(5, min(120, (int) ($data['delay_seconds'] ?? 20)));
    $mode = ($data['mode'] ?? 'newsletter') === 'cta' ? 'cta' : 'newsletter';
    $memoryKey = 'sewa.exitcap.'.substr(md5((string) ($data['heading'] ?? '')), 0, 10);
    $ctas = is_array($data['ctas'] ?? null) ? array_values($data['ctas']) : [];
@endphp

<section {{ $attributes }} class="contents"
    x-data="{
        shown: false,
        allowed() { return localStorage.getItem(@js($memoryKey)) === null; },
        open() {
            if (this.shown || ! this.allowed()) { return; }
            this.shown = true;
            localStorage.setItem(@js($memoryKey), String(Date.now()));
            this.$nextTick(() => this.$refs.panel?.focus());
        },
        init() {
            // Never on first paint: every trigger path is deferred.
            const trigger = @js($trigger);
            if (trigger === 'exit') {
                document.addEventListener('mouseout', (e) => {
                    if (! e.relatedTarget && e.clientY <= 0) { this.open(); }
                }, { passive: true });
            } else if (trigger === 'scroll') {
                const onScroll = () => {
                    const pct = (window.scrollY + window.innerHeight) / document.documentElement.scrollHeight * 100;
                    if (pct >= @js($scrollPct)) { this.open(); window.removeEventListener('scroll', onScroll); }
                };
                window.addEventListener('scroll', onScroll, { passive: true });
            } else {
                setTimeout(() => this.open(), @js($delaySeconds * 1000));
            }
        },
    }">
    <div x-cloak x-show="shown" class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
         role="dialog" aria-modal="true" aria-label="{{ ($data['heading'] ?? 'Offer') }}" x-trap.inert.noscroll="shown">
        <div class="absolute inset-0 bg-ink/60 backdrop-blur-sm" @click="shown = false" aria-hidden="true"></div>

        <div class="relative w-full max-w-md rounded-2xl bg-paper p-6 shadow-xl" x-show="shown" x-transition.opacity x-ref="panel" tabindex="-1"
             @keydown.escape.window="shown = false">
            <button type="button" class="absolute end-3 top-3 inline-flex h-11 w-11 items-center justify-center rounded-full text-ink-muted hover:bg-paper-3"
                    @click="shown = false" aria-label="Close">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
            </button>

            <h2 class="font-display pe-8 text-2xl">{{ $data['heading'] ?? '' }}</h2>
            @if (($data['copy'] ?? '') !== '')
                <p class="mt-3 text-sm text-ink-soft">{{ $data['copy'] }}</p>
            @endif

            <div class="mt-5">
                @if ($mode === 'newsletter')
                    <livewire:leads.newsletter-signup :compact="true" />
                @else
                    <div class="flex flex-wrap gap-3">
                        @foreach ($ctas as $cta)
                            @if (($cta['label'] ?? '') && ($cta['url'] ?? ''))
                                <a href="{{ $cta['url'] }}" class="inline-flex min-h-[44px] items-center rounded-full {{ ($cta['variant'] ?? 'primary') === 'primary' ? 'bg-brand text-brand-ink' : 'border border-line text-ink' }} px-5 text-sm font-semibold">
                                    {{ $cta['label'] }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            <p class="mt-3 text-[11px] text-ink-muted">Shown at most once a week — we're polite, not pushy.</p>
        </div>
    </div>
</section>
