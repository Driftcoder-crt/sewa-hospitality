{{-- Job postings table (06-hr §4.1) — status machine actions inline. --}}
<div>
    <h1 class="font-display text-2xl">Job postings</h1>
    <p class="mt-1 text-sm text-ink-muted">draft → open → paused → closed · closed pages stay online with "see similar" (never a 404).</p>

    <form wire:submit="create" class="mt-4 flex flex-col gap-2 rounded-xl border border-line bg-paper-2 p-4 sm:flex-row sm:items-end">
        <div class="flex-1">
            <label for="job-title" class="text-xs uppercase text-ink-muted">New role title</label>
            <input id="job-title" type="text" wire:model.live.debounce.300ms="title" placeholder="e.g. Senior Relocation Consultant"
                   class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
            @error('title') <p class="text-xs text-danger-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="job-dept" class="text-xs uppercase text-ink-muted">Department</label>
            <select id="job-dept" wire:model.live="department" class="mt-1 min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm">
                @foreach ($departments as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1">
            <label for="job-loc" class="text-xs uppercase text-ink-muted">Location text</label>
            <input id="job-loc" type="text" wire:model.live.debounce.300ms="locationText" placeholder="Gurugram, Haryana"
                   class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
            @error('locationText') <p class="text-xs text-danger-500">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand px-4 text-sm font-semibold text-brand-ink">
            Create draft
        </button>
    </form>

    <div class="mt-4 flex items-center gap-2">
        <input type="search" wire:model.live.debounce.300ms="q" placeholder="Search roles…" aria-label="Search roles"
               class="min-h-[44px] w-full max-w-xs rounded-lg border border-line bg-paper px-3 text-sm">
        <select wire:model.live="statusFilter" class="min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm" aria-label="Filter by status">
            <option value="">All statuses</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-3 overflow-hidden rounded-xl border border-line bg-paper-2">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-muted">
                    <th class="px-3 py-3">Role</th>
                    <th class="px-3 py-3">Status</th>
                    <th class="hidden px-3 py-3 md:table-cell">Applications</th>
                    <th class="hidden px-3 py-3 lg:table-cell">Closes</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($postings as $posting)
                    <tr class="border-b border-line last:border-0">
                        <td class="px-3 py-3">
                            <a href="{{ route('admin.jobs.edit', ['job' => $posting->getKey()]) }}" class="font-medium hover:text-brand">{{ $posting->title }}</a>
                            <div class="text-xs text-ink-muted">{{ $posting->department->label() }} · {{ $posting->location_text }}</div>
                        </td>
                        <td class="px-3 py-3"><span class="inline-flex rounded-full border border-line px-2.5 py-1 text-xs">{{ $posting->status->label() }}</span></td>
                        <td class="hidden px-3 py-3 md:table-cell">{{ $posting->applications_count }}</td>
                        <td class="hidden px-3 py-3 text-xs text-ink-muted lg:table-cell">{{ $posting->closesLabel() ?? '—' }}</td>
                        <td class="px-3 py-3">
                            <div class="flex justify-end gap-2 text-xs font-medium">
                                @if (in_array($posting->status->value, ['draft', 'paused']))
                                    <button type="button" wire:click="open('{{ $posting->id }}')" wire:confirm="Open applications for this role?" class="text-brand hover:underline">Open</button>
                                @endif
                                @if ($posting->status->value === 'open')
                                    <button type="button" wire:click="pause('{{ $posting->id }}')" class="text-ink-muted hover:underline">Pause</button>
                                    <button type="button" wire:click="close('{{ $posting->id }}')" wire:confirm="Close this role? The page stays online." class="text-danger-500 hover:underline">Close</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-10 text-center text-sm text-ink-muted">No postings yet — create the first draft above.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $postings->links() }}</div>
</div>
