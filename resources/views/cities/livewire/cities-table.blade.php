<div class="admin-screen">
@section('title', 'Cities — Sewa Admin')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">Cities</h1>
            <p class="eyebrow mt-1 text-ink-muted">Cities &amp; Housing · coverage truth enforced</p>
        </div>
        <button wire:click="create" type="button"
                class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">New city</button>
    </div>

    <select wire:model.live="status" aria-label="Filter by status"
            class="mt-4 min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm text-ink">
        <option value="">All statuses</option>
        @foreach ($statuses as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>

    <div class="mt-4 overflow-x-auto rounded-xl border border-line bg-paper-2">
        <table class="w-full min-w-[640px] text-sm">
            <thead>
                <tr class="border-b border-line text-ink-muted">
                    <th class="px-4 py-3 text-start font-semibold">City</th>
                    <th class="px-4 py-3 text-start font-semibold">State</th>
                    <th class="px-4 py-3 text-start font-semibold">Hub</th>
                    <th class="px-4 py-3 text-start font-semibold">Status</th>
                    <th class="px-4 py-3 text-end font-semibold"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cities as $city)
                    <tr class="border-b border-line/60">
                        <td class="px-4 py-3 font-medium text-ink">{{ $city->name }} <code class="ms-1 text-xs text-ink-muted">/cities/{{ $city->slug }}</code></td>
                        <td class="px-4 py-3 text-ink-soft">{{ $city->state }}</td>
                        <td class="px-4 py-3">{{ $city->is_hub ? '✓' : '—' }}</td>
                        <td class="px-4 py-3 text-xs {{ $city->status->isPublic() ? 'text-ink' : 'text-ink-muted' }}">{{ $city->status->label() }}</td>
                        <td class="px-4 py-3 text-end">
                            <a href="{{ route('admin.cities.edit', ['city' => $city->getKey()]) }}"
                               class="inline-flex min-h-[36px] items-center rounded-lg bg-brand px-3 text-xs font-semibold text-brand-ink hover:opacity-90">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-ink-soft">No cities yet — run CitiesSeeder.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $cities->links() }}</div>

    @if ($backlog->isNotEmpty())
        <div class="mt-6 rounded-xl border border-line bg-paper-2 p-4">
            <h2 class="font-display text-lg">Search backlog — zero-result queries (editorial tickets)</h2>
            <ul class="mt-3 flex flex-wrap gap-2">
                @foreach ($backlog as $query)
                    <li class="rounded-full border border-line bg-paper px-3 py-1.5 text-xs text-ink-soft">
                        “{{ $query->term }}” <span class="text-ink-muted">× {{ $query->count }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
