{{-- /search — grouped tabs island (08-search §3). noindex, follow (§6):
    crawlable links out, never a search-page index hit. --}}
<div>
    {{-- aria-live announced region; debounce handled by wire:model.live.250ms --}}
    <section data-theme="light" class="px-4 py-12 md:px-6">
        <div class="container mx-auto max-w-3xl">
            <p class="eyebrow text-ink-muted">SEARCH</p>
            <h1 class="font-display mt-2 text-3xl">What are you looking for?</h1>

            <input type="search" wire:model.live.debounce.250ms="q"
                   placeholder="Try “relocation pune” or “serviced apartments gurugram”…"
                   class="mt-5 min-h-[52px] w-full rounded-xl border border-line bg-paper px-4 text-base text-ink outline-none focus:border-brand"
                   aria-label="Search the site">

            @if ($q !== '')
                @if ($results['total'] > 0)
                    <div class="mt-5 flex flex-wrap gap-2" role="tablist" aria-label="Result groups">
                        @foreach ($tabs as $key => $group)
                            <button type="button" wire:click="$set('activeTab', '{{ $key }}')" role="tab"
                                    aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
                                    class="inline-flex min-h-[44px] items-center rounded-full border px-4 text-sm font-semibold {{ $activeTab === $key ? 'border-brand bg-brand text-brand-ink' : 'border-line text-ink-soft hover:bg-paper-3' }}">
                                {{ $group['label'] }} ({{ $group['count'] }})
                            </button>
                        @endforeach
                    </div>

                    <ul class="mt-5 flex flex-col gap-2">
                        @foreach ($tabs->get($activeTab)['hits'] ?? [] as $hit)
                            <li>
                                <a href="{{ $hit->publicPath() }}"
                                   class="flex min-h-[44px] flex-col rounded-xl border border-line bg-paper p-4 hover:bg-paper-2">
                                    <span class="text-sm font-semibold text-ink">{{ $hit->name ?? $hit->title ?? $hit->getKey() }}</span>
                                    @if (isset($hit->short_desc) && $hit->short_desc)
                                        <span class="mt-0.5 text-xs text-ink-soft">{{ \Illuminate\Support\Str::limit(strip_tags($hit->short_desc), 110) }}</span>
                                    @elseif (isset($hit->description) && $hit->description)
                                        <span class="mt-0.5 text-xs text-ink-soft">{{ \Illuminate\Support\Str::limit(strip_tags($hit->description), 110) }}</span>
                                    @elseif (isset($hit->meta_description) && $hit->meta_description)
                                        <span class="mt-0.5 text-xs text-ink-soft">{{ \Illuminate\Support\Str::limit(strip_tags((string) $hit->meta_description), 110) }}</span>
                                    @endif
                                    <span class="mt-1 text-xs text-ink-muted">{{ $hit->publicPath() }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="mt-6">
                        <x-empty-state title="Nothing matches “{{ $q }}” — yet"
                            description="Zero-result searches go straight to our content team. Meanwhile, these are the places most people need:">
                            <div class="flex flex-wrap justify-center gap-2">
                                <x-button href="/services" variant="secondary" size="sm">All services</x-button>
                                <x-button href="/cities" variant="secondary" size="sm">City guides</x-button>
                                <x-button href="/housing" variant="secondary" size="sm">Housing</x-button>
                            </div>
                        </x-empty-state>
                    </div>
                @endif
            @else
                <p class="mt-6 text-sm text-ink-soft">Search across services, city guides, housing and pages. Results appear as you type — press enter to keep the URL shareable.</p>
            @endif
        </div>
    </section>
</div>
