{{-- D6 · Team Grid (section-library §5): module-fed from
     employees.is_public — real people cards, tap-to-bio pages, honest
     zero-state until the HR directory has public entries. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $limit = max(1, min(24, (int) ($data['limit'] ?? 8)));
    $department = (string) ($data['department'] ?? 'all');

    $people = \App\Modules\Careers\Models\Employee::query()
        ->public()
        ->with(['photo', 'officeCity:id,name'])
        ->when($department !== 'all' && \App\Modules\Careers\Enums\Department::tryFrom($department) !== null,
            fn ($query) => $query->where('department', $department))
        ->orderBy('sort')
        ->orderBy('full_name')
        ->take($limit)
        ->get();
@endphp

<section {{ $attributes }} data-theme="light">
    <div class="px-4 py-12 md:px-6 md:py-16">
        <div class="container mx-auto">
            @if (($data['heading'] ?? '') !== '')
                <h2 class="font-display text-2xl md:text-3xl">{{ $data['heading'] }}</h2>
            @endif

            @if ($people->isEmpty())
                <p class="mt-6 rounded-2xl border border-dashed border-line bg-paper-2 p-8 text-center text-sm text-ink-muted">
                    Team profiles are being prepared — real people, no placeholder headshots.
                </p>
            @else
                <ul class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($people as $person)
                        <li>
                            <a href="{{ $person->publicPath() }}"
                               class="group flex h-full flex-col overflow-hidden rounded-2xl border border-line bg-paper-2 transition-shadow hover:shadow-md">
                                <div class="aspect-square w-full overflow-hidden bg-paper-3">
                                    @if ($person->photo)
                                        <x-media :media="$person->photo" conversion="card" class="h-full w-full [&>img]:h-full [&>img]:w-full [&>img]:object-cover" />
                                    @else
                                        <div class="flex h-full w-full items-center justify-center font-display text-3xl text-ink-muted">
                                            @php($initials = collect(explode(' ', $person->full_name))->map(fn (string $part) => mb_substr($part, 0, 1))->implode(''))
                                            {{ $initials }}
                                        </div>
                                    @endif
                                </div>
                                <div class="flex flex-1 flex-col p-4">
                                    <span class="font-semibold text-ink group-hover:text-brand">{{ $person->full_name }}</span>
                                    <span class="mt-0.5 text-sm text-ink-soft">{{ $person->designation }}</span>
                                    @if ($person->languages() !== [])
                                        <span class="mt-2 text-xs text-ink-muted">{{ implode(' · ', array_slice($person->languages(), 0, 4)) }}</span>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</section>
