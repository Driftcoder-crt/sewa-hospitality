{{-- <x-footer> — columns, NAP from settings (byte-identical single
     source), menu-driven links, copyright. NAP values render from
     Identity::current() — never retyped (brand doc §9). --}}
@props([
    'items' => null, // Collection<int, MenuItem> resolved by the layout
])

@php
    $identity = \App\Support\Cms\Identity::current();
    $nav = $items ?? collect();
    $top = $nav->whereNull('parent_id')->values();
@endphp

<footer role="contentinfo" class="mt-auto border-t border-line bg-paper-2">
    <div class="container mx-auto grid gap-8 p-6 md:grid-cols-4 md:p-10">
        <div class="md:col-span-2">
            <p class="font-display text-lg font-semibold">{{ $identity['brand'] }}</p>
            <p class="eyebrow mt-1 text-ink-muted">{{ $identity['slogan'] }}</p>
            <address class="mt-4 text-sm not-italic leading-relaxed text-ink-soft">
                {{ $identity['address']['street'] ?? '' }}<br>
                {{ $identity['address']['city'] ?? '' }}, {{ $identity['address']['state'] ?? '' }} {{ $identity['address']['postalCode'] ?? '' }}, {{ $identity['address']['country'] ?? '' }}
            </address>
            <p class="mt-3 flex flex-wrap gap-x-5 gap-y-1">
                <a href="tel:{{ $identity['telephone_e164'] }}" class="inline-flex min-h-[44px] items-center text-sm text-ink hover:text-ink-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    {{ $identity['telephone'] }}
                </a>
                <a href="mailto:{{ $identity['email'] }}" class="inline-flex min-h-[44px] items-center text-sm text-ink hover:text-ink-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    {{ $identity['email'] }}
                </a>
            </p>
        </div>

        @foreach ($top as $group)
            <nav aria-label="{{ $group->label }}" class="text-sm">
                <p class="eyebrow mb-3 text-ink-muted">{{ $group->label }}</p>
                <ul class="flex flex-col gap-1">
                    @foreach ($nav->where('parent_id', $group->getKey())->values() as $item)
                        @php($href = $item->href())
                        @if ($href)
                            <li>
                                <a href="{{ $href }}"
                                   @if (str_starts_with((string) $href, 'http') && ! str_contains((string) $href, parse_url(config('app.url', 'https://sewahospitality.com'), PHP_URL_HOST))) target="_blank" rel="noopener" @endif
                                   class="inline-flex min-h-[44px] items-center text-ink-soft hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                    {{ $item->label }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </nav>
        @endforeach

        {{-- Language switcher (11-multilingual §3): plain native names,
             one click to EN from everywhere. Links ride the explicit
             chooser so the preference cookie is only ever set on a
             deliberate click — never by detection. --}}
        @php($i18n = $i18n ?? [])
        @if (count($i18n['locales'] ?? []) > 1)
            <nav aria-label="Language" class="text-sm">
                <p class="eyebrow mb-3 text-ink-muted">Language</p>
                <ul class="flex flex-wrap gap-x-4 gap-y-1">
                    @foreach ($i18n['locales'] as $code => $native)
                        <li>
                            <a href="{{ route('locale.choose', ['code' => $code, 'to' => request()->path()]) }}"
                               hreflang="{{ $code }}"
                               @if (($i18n['current'] ?? 'en') === $code) aria-current="true" @endif
                               class="inline-flex min-h-[44px] items-center {{ ($i18n['current'] ?? 'en') === $code ? 'font-semibold text-ink' : 'text-ink-soft hover:text-ink' }} focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                {{ $native }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </div>

    <div class="border-t border-line">
        <div class="container mx-auto flex flex-col gap-2 p-4 text-xs text-ink-muted md:flex-row md:items-center md:justify-between">
            <p>© {{ date('Y') }} {{ $identity['legalName'] }}</p>
            <p>{{ $identity['url'] ? parse_url($identity['url'], PHP_URL_HOST) : '' }}</p>
        </div>
    </div>
</footer>
