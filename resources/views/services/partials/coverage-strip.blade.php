{{-- Coverage strip (services doc §3 + cities doc §5 "coverage truth"):
     renders ONLY published cities with real city_services rows. --}}
@if ($coverage->isNotEmpty())
    <section data-theme="light" class="border-t border-line px-4 py-10 md:px-6">
        <div class="container mx-auto">
            <p class="eyebrow text-ink-muted">AVAILABLE IN</p>
            <ul class="mt-3 flex flex-wrap gap-2">
                @foreach ($coverage as $city)
                    <li>
                        <a href="/cities/{{ $city->slug }}"
                           class="inline-flex min-h-[44px] items-center rounded-full border border-line bg-paper px-4 text-sm font-medium text-ink-soft hover:bg-paper-3 hover:text-ink"
                           @if ($city->note) title="{{ $city->note }}" @endif>
                            {{ $city->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
