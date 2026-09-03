{{-- <x-site-header> — desktop nav always visible (never hamburger-only,
     the reference's defect), off-canvas mobile with focus trap, sticky
     with shrink-on-scroll, 44px targets, token-driven + RTL-safe. --}}
@props([
    'items' => null, // Collection<int, MenuItem> resolved by the layout
])

@php
    $identity = \App\Support\Cms\Identity::current();
    $nav = $items ?? collect();
@endphp

<header x-data="{ mobile: false, scrolled: false }"
        x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 8, { passive: true })"
        role="banner"
        class="sticky top-0 z-30 border-b border-line bg-paper/95 backdrop-blur transition-shadow"
        :class="scrolled && 'shadow-sm'">
    <div class="container mx-auto px-4 md:px-6 flex h-[72px] max-md:h-16 items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="inline-flex min-h-[44px] items-center" aria-label="{{ $identity['brand'] }} — home">
            <span class="inline-flex items-baseline gap-2">
                <span class="font-display text-lg font-semibold tracking-tight">{{ $identity['brand'] }}</span>
                <span class="eyebrow hidden text-ink-muted sm:inline">{{ $identity['slogan'] }}</span>
            </span>
        </a>

        {{-- Desktop nav — always visible (never hover-only, never hamburger-only) --}}
        <nav aria-label="Primary" class="hidden md:block">
            <ul class="flex items-center gap-1">
                @foreach ($nav as $item)
                    @php($href = $item->href())
                    @if ($href)
                        <li>
                            <a href="{{ $href }}"
                               @if (request()->path() === ltrim((string) $href, '/')) aria-current="page" @endif
                               class="inline-flex min-h-[44px] items-center rounded-lg px-3 text-sm font-medium text-ink-soft hover:bg-paper-3 hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                {{ $item->label }}
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </nav>

        <div class="flex items-center gap-2">
            <a href="tel:{{ $identity['telephone_e164'] }}"
               class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                {{ $identity['telephone'] }}
            </a>

            {{-- Mobile menu button --}}
            <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-ink hover:bg-paper-3 md:hidden focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                    @click="mobile = true"
                    :aria-expanded="mobile.toString()"
                    aria-controls="mobile-nav"
                    aria-label="Open menu">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>

    {{-- Off-canvas mobile nav with focus trap --}}
    <div id="mobile-nav" x-cloak x-show="mobile"
         class="fixed inset-0 z-40 md:hidden"
         role="dialog" aria-modal="true" aria-label="Menu"
         @keydown.escape.window="mobile = false"
         x-trap.inert.noscroll="mobile">
        <div class="absolute inset-0 bg-ink-900/40" @click="mobile = false" aria-hidden="true"></div>
        <nav class="absolute inset-y-0 end-0 flex w-72 max-w-[85vw] flex-col border-s border-line bg-paper p-4" aria-label="Mobile">
            <div class="flex items-center justify-between border-b border-line pb-3">
                <span class="font-display font-semibold">{{ $identity['brand'] }}</span>
                <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-lg hover:bg-paper-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                        @click="mobile = false" aria-label="Close menu">
                    <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.72 4.72a.75.75 0 0 1 1.06 0L10 8.94l4.22-4.22a.75.75 0 1 1 1.06 1.06L11.06 10l4.22 4.22a.75.75 0 1 1-1.06 1.06L10 11.06l-4.22 4.22a.75.75 0 0 1-1.06-1.06L8.94 10 4.72 5.78a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                </button>
            </div>
            <ul class="mt-3 flex flex-col gap-1">
                @foreach ($nav as $item)
                    @php($href = $item->href())
                    @if ($href)
                        <li>
                            <a href="{{ $href }}" @click="mobile = false"
                               class="flex min-h-[44px] items-center rounded-lg px-3 text-base font-medium text-ink hover:bg-paper-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                {{ $item->label }}
                            </a>
                        </li>
                    @endif
                @endforeach
                <li class="mt-2">
                    <a href="tel:{{ $identity['telephone_e164'] }}"
                       class="flex min-h-[44px] items-center justify-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink">
                        {{ $identity['telephone'] }}
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</header>
