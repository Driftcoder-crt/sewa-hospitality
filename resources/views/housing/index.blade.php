{{-- /housing — national inventory browser (cities doc §3): filters
     (city × type × tier × bedrooms × price band) as shareable query
     params; zero results → friendly alternatives (§6, never dead-end). --}}
@extends('layouts.app')

@section('title', 'Housing — serviced apartments & corporate housing — Sewa Hospitality')
@section('meta_description', 'Browse Sewa Verified serviced apartments, corporate housing and guest houses across India — honest from-rates, verified standards, real availability.')

@push('head')
    <link rel="canonical" href="{{ rtrim(config('app.url', 'https://sewahospitality.com'), '/') }}/housing">
    <x-site.hreflang />
    <meta name="robots" content="index, follow, max-image-preview:large">
@endpush

@section('content')
    <section data-theme="light" class="px-4 py-14 md:px-6 md:py-16">
        <div class="container mx-auto max-w-3xl">
            <p class="eyebrow text-ink-muted">HOUSING INVENTORY</p>
            <h1 class="font-display mt-3 text-4xl md:text-5xl">Stay verified. Stay cared for.</h1>
            <p class="mt-4 text-lg text-ink-soft">Every listed home passes our own inspection standard — <a href="/housing/verified" class="font-semibold text-brand underline underline-offset-2">see what Sewa Verified means</a>.</p>
        </div>
    </section>

    <section data-theme="light" class="px-4 pb-6 md:px-6">
        <form method="GET" action="/housing" class="container mx-auto rounded-2xl border border-line bg-paper-2 p-4">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <label class="block text-sm">
                    <span class="text-xs font-semibold text-ink-soft">City</span>
                    <select name="city" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                        <option value="">All cities</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->slug }}" @if ($filters['city'] === $city->slug) selected @endif>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="text-xs font-semibold text-ink-soft">Type</span>
                    <select name="type" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                        <option value="">All types</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @if ($filters['type'] === $value) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="text-xs font-semibold text-ink-soft">Tier</span>
                    <select name="tier" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                        <option value="">All tiers</option>
                        @foreach ($tiers as $value => $label)
                            <option value="{{ $value }}" @if ($filters['tier'] === $value) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="text-xs font-semibold text-ink-soft">Bedrooms</span>
                    <select name="bedrooms" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                        <option value="">Any</option>
                        @foreach ([1, 2, 3, 4] as $n)
                            <option value="{{ $n }}" @if ($filters['bedrooms'] === (string) $n) selected @endif>{{ $n }}+</option>
                        @endforeach
                    </select>
                </label>
                <div class="flex items-end gap-2">
                    <label class="block w-full text-sm">
                        <span class="text-xs font-semibold text-ink-soft">Monthly band</span>
                        <select name="price" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                            <option value="">Any</option>
                            <option value="0-40000" @if ($filters['price'] === '0-40000') selected @endif>Under ₹40k</option>
                            <option value="40000-80000" @if ($filters['price'] === '40000-80000') selected @endif>₹40k – ₹80k</option>
                            <option value="80000+" @if ($filters['price'] === '80000+') selected @endif>₹80k+</option>
                        </select>
                    </label>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <x-button type="submit" size="sm">Apply filters</x-button>
                <a href="/housing" class="inline-flex min-h-[44px] items-center text-sm text-ink-soft hover:text-ink">Clear</a>
            </div>
        </form>
    </section>

    <section data-theme="light" class="px-4 pb-16 md:px-6">
        <div class="container mx-auto">
            @forelse ($units as $unit)
                @php($loopFirst = $loop->first)
                @if ($loopFirst)
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @endif
                        <x-housing-card :unit="$unit" />
                @if ($loop->last)
                    </div>
                    <div class="mt-6">{{ $units->links() }}</div>
                @endif
            @empty
                <x-empty-state title="No homes match these filters yet"
                    description="Inventory updates continuously. Try a wider filter — or tell us your brief and we will send current options with dated rates.">
                    <div class="flex flex-wrap justify-center gap-2">
                        <x-button href="/housing" variant="secondary" size="sm">Clear filters</x-button>
                        <x-button href="/contact" size="sm">Request options</x-button>
                    </div>
                </x-empty-state>
            @endforelse
        </div>
    </section>
@endsection
