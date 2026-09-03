{{-- E6 · Countdown Promo (section-library §6): timezone-correct
     deadline countdown rendered client-side (Alpine); past the
     deadline it expires GRACEFULLY into an evergreen state — no broken
     clock, no false urgency. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $theme = ($data['theme'] ?? 'deep') === 'brand' ? 'brand' : 'deep';
    // Server truth: is the deadline already past? (Client may skew clocks.)
    $expired = false;
    try {
        $deadline = \Illuminate\Support\Carbon::parse((string) ($data['deadline'] ?? ''), 'Asia/Kolkata');
        $expired = now()->gte($deadline);
    } catch (\Throwable) {
        $deadline = null;
        $expired = true; // an unparsable deadline never fakes urgency
    }
@endphp

<section {{ $attributes }}>
    <div data-theme="{{ $theme }}" class="px-4 py-12 md:px-6 md:py-16">
        <div class="container mx-auto max-w-3xl text-center">
            <h2 class="font-display text-3xl text-paper md:text-4xl">{{ $data['heading'] ?? '' }}</h2>

            @if (($data['copy'] ?? '') !== '')
                <p class="mt-3 text-paper/85">{{ $data['copy'] }}</p>
            @endif

            @if (! $expired)
                <div class="mt-6" x-data="{
                        deadline: new Date(@js(optional($deadline)?->toIso8601String())),
                        days: '0', hours: '00', minutes: '00',
                        tick() {
                            const diff = Math.max(0, this.deadline.getTime() - Date.now());
                            this.days = String(Math.floor(diff / 86400000));
                            this.hours = String(Math.floor(diff / 3600000) % 24).padStart(2, '0');
                            this.minutes = String(Math.floor(diff / 60000) % 60).padStart(2, '0');
                            if (diff === 0) { this.$el.parentElement.innerHTML = '<p class=&quot;text-paper/70&quot;>This offer window has closed — but our consultants are always on. Talk to us.</p>'; }
                        }
                    }"
                    x-init="tick(); setInterval(() => tick(), 30000)"
                    aria-live="off">
                    <div class="mx-auto flex max-w-sm items-stretch justify-center gap-3 font-display text-paper">
                        <div class="flex-1 rounded-xl border border-paper/25 px-3 py-3">
                            <span class="block text-3xl" x-text="days">0</span>
                            <span class="mt-1 block text-xs uppercase tracking-wide text-paper/70">Days</span>
                        </div>
                        <div class="flex-1 rounded-xl border border-paper/25 px-3 py-3">
                            <span class="block text-3xl" x-text="hours">00</span>
                            <span class="mt-1 block text-xs uppercase tracking-wide text-paper/70">Hours</span>
                        </div>
                        <div class="flex-1 rounded-xl border border-paper/25 px-3 py-3">
                            <span class="block text-3xl" x-text="minutes">00</span>
                            <span class="mt-1 block text-xs uppercase tracking-wide text-paper/70">Minutes</span>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-paper/60">Deadline {{ optional($deadline)?->timezone('Asia/Kolkata')->format('d M Y, H:i') }} IST</p>
                </div>
            @else
                <p class="mt-6 text-paper/70">This offer window has closed — but our consultants are always on. Talk to us.</p>
            @endif

            @if (($data['cta_label'] ?? '') && ($data['cta_url'] ?? ''))
                <a href="{{ $data['cta_url'] }}" class="mt-8 inline-flex min-h-[44px] items-center rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink hover:opacity-90">
                    {{ $data['cta_label'] }}
                </a>
            @endif
        </div>
    </div>
</section>
