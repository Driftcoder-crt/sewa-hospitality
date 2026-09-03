<div class="admin-screen">
@section('title', 'Moves — Sewa Admin')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">Moves</h1>
            <p class="eyebrow mt-1 text-ink-muted">Portal ops · relocation engagements</p>
        </div>
        <button type="button" wire:click="$toggle('creating')"
                class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">
            New move
        </button>
    </div>

    @if ($creating)
        <form wire:submit="createDraft" class="mt-4 grid gap-3 rounded-xl border border-line bg-paper-2 p-5 md:grid-cols-3">
            <div>
                <label class="text-xs font-semibold text-ink-muted" for="creating">Relocatee name / assignee</label>
                <input id="creating" type="text" wire:model="creating" required
                       class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                @error('creating') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold text-ink-muted" for="createOrg">Organization</label>
                <select id="createOrg" wire:model="createOrg" required
                        class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                    <option value="">Choose…</option>
                    @foreach ($organizations as $organization)
                        <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                    @endforeach
                </select>
                @error('createOrg') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold text-ink-muted" for="createMoveDate">Target move date</label>
                <input id="createMoveDate" type="date" wire:model="createMoveDate"
                       class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
            </div>
            <div class="md:col-span-3">
                <button type="submit" class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink hover:opacity-90">
                    Create draft → editor
                </button>
            </div>
        </form>
    @endif

    {{-- Filters --}}
    <div class="mt-4 flex flex-wrap items-center gap-2">
        <input type="search" wire:model.live.debounce.400ms="q" placeholder="Reference, name, city…"
               class="min-h-[44px] w-64 rounded-full border border-line bg-paper-2 px-4 text-sm focus:border-brand focus:outline-none">
        <select wire:model.live="stage" class="min-h-[44px] rounded-full border border-line bg-paper-2 px-3 text-sm focus:border-brand focus:outline-none">
            <option value="">All stages</option>
            @foreach ($stages as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="status" class="min-h-[44px] rounded-full border border-line bg-paper-2 px-3 text-sm focus:border-brand focus:outline-none">
            <option value="">All statuses</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="org" class="min-h-[44px] max-w-xs rounded-full border border-line bg-paper-2 px-3 text-sm focus:border-brand focus:outline-none">
            <option value="">All organizations</option>
            @foreach ($organizations as $organization)
                <option value="{{ $organization->id }}">{{ $organization->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-line bg-paper-2">
        <table class="w-full min-w-[760px] text-sm">
            <thead>
                <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-muted">
                    <th class="px-4 py-3 font-semibold">Reference</th>
                    <th class="px-4 py-3 font-semibold">Relocatee</th>
                    <th class="px-4 py-3 font-semibold">Organization</th>
                    <th class="px-4 py-3 font-semibold">Route</th>
                    <th class="px-4 py-3 font-semibold">Stage</th>
                    <th class="px-4 py-3 font-semibold">Consultant</th>
                    <th class="px-4 py-3"><span class="sr-only">Open</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($moves as $move)
                    <tr class="border-b border-line last:border-0">
                        <td class="px-4 py-3 font-medium">{{ $move->reference }}</td>
                        <td class="px-4 py-3">{{ $move->employee?->name ?? $move->assignee_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-ink-soft">{{ $move->organization?->name }}</td>
                        <td class="px-4 py-3 text-ink-soft">{{ $move->origin_city }} → {{ $move->destinationCity?->name }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-3 py-0.5 text-xs font-semibold {{ $move->status->value === 'cancelled' ? 'bg-paper-3 text-ink-muted' : 'bg-brand/10 text-brand' }}">
                                {{ $move->stage->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-ink-soft">{{ $move->consultant?->name ?? 'Unassigned' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.moves.edit', $move) }}" wire:navigate
                               class="text-sm font-medium text-brand hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-ink-soft">No moves match the filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $moves->links() }}
</div>
