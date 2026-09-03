<div class="admin-screen">
@section('title', 'Housing units — Sewa Admin')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">Housing units</h1>
            <p class="eyebrow mt-1 text-ink-muted">Cities &amp; Housing · Sewa Verified queue below</p>
        </div>
        <button wire:click="create" type="button"
                class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">New unit</button>
    </div>

    @if ($reverification->isNotEmpty())
        <div class="mt-4 rounded-xl border border-warning-500/40 bg-warning-500/10 p-4">
            <h2 class="text-sm font-semibold text-ink">Re-verification queue (badge older than 6 months)</h2>
            <ul class="mt-2 flex flex-wrap gap-2">
                @foreach ($reverification as $unit)
                    <li class="flex items-center gap-2 rounded-full border border-line bg-paper px-3 py-1.5 text-xs">
                        <span class="font-medium text-ink">{{ $unit->name }}</span>
                        <span class="text-ink-muted">{{ $unit->city?->name }} · {{ $unit->verified_at?->format('M Y') }}</span>
                        <button type="button" wire:click="reverify('{{ $unit->getKey() }}')" class="font-semibold text-brand hover:underline">Re-verify</button>
                        <button type="button" wire:click="expireBadge('{{ $unit->getKey() }}')" wire:confirm="Expire the Sewa Verified badge for this unit?" class="font-semibold text-ink-muted hover:text-ink">Expire</button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-4 overflow-x-auto rounded-xl border border-line bg-paper-2">
        <table class="w-full min-w-[720px] text-sm">
            <thead>
                <tr class="border-b border-line text-ink-muted">
                    <th class="px-4 py-3 text-start font-semibold">Unit</th>
                    <th class="px-4 py-3 text-start font-semibold">City / type</th>
                    <th class="px-4 py-3 text-start font-semibold">From-rate</th>
                    <th class="px-4 py-3 text-start font-semibold">Verified</th>
                    <th class="px-4 py-3 text-start font-semibold">Live</th>
                    <th class="px-4 py-3 text-end font-semibold"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($units as $unit)
                    <tr class="border-b border-line/60">
                        <td class="px-4 py-3 font-medium text-ink">{{ $unit->name }}</td>
                        <td class="px-4 py-3 text-xs text-ink-soft">{{ $unit->city?->name }} · {{ $unit->type->label() }} · {{ $unit->tier->label() }}</td>
                        <td class="px-4 py-3 text-xs text-ink-soft">
                            {{ $unit->rateLabel() ?? '—' }}
                            @if ($unit->isRateStale()) <span class="ms-1 rounded-full bg-warning-500/15 px-2 py-0.5 font-semibold text-ink">stale</span> @endif
                        </td>
                        <td class="px-4 py-3 text-xs {{ $unit->isVerified() ? 'text-ink' : 'text-ink-muted' }}">{{ $unit->verified_at?->format('M Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <button type="button" wire:click="togglePublished('{{ $unit->getKey() }}')"
                                    class="inline-flex min-h-[36px] items-center rounded-full border px-3 text-xs font-semibold {{ $unit->published ? 'border-success-500/40 text-ink' : 'border-line text-ink-muted' }}">
                                {{ $unit->published ? 'Live' : 'Draft' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <a href="{{ route('admin.housing.edit', ['unit' => $unit->getKey()]) }}"
                               class="inline-flex min-h-[36px] items-center rounded-lg bg-brand px-3 text-xs font-semibold text-brand-ink hover:opacity-90">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-ink-soft">No units yet — inventory is entered by ops (never seeded into production).</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $units->links() }}</div>
</div>
