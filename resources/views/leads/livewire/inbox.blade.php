{{-- CRM inbox (03-leads-crm §4.1) — SLA countdown chips, filters,
     bulk actions, wire:poll refresh with new-lead toast. --}}
<div>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl">Leads inbox</h1>
            <p class="mt-1 text-sm text-ink-muted">{{ $newCount }} new · SLA promises: contact 2h · quote 4h · callback 2h (business hours, IST)</p>
        </div>

        <div class="flex items-center gap-2">
            @if ($canExport)
                <button type="button" wire:click="exportCsv" wire:loading.attr="disabled" wire:target="exportCsv"
                        class="inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm font-medium hover:bg-paper-3">
                    Export CSV
                </button>
            @endif
        </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-2" wire:poll.30s>
        <input type="search" wire:model.live.debounce.300ms="q" placeholder="Search name, email, phone, company…"
               class="min-h-[44px] w-full max-w-xs rounded-lg border border-line bg-paper px-3 text-sm"
               aria-label="Search leads">
        <select wire:model.live="status" class="min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm" aria-label="Filter by status">
            <option value="">All statuses</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="source" class="min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm" aria-label="Filter by source">
            <option value="">All sources</option>
            @foreach ($sources as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="serviceId" class="min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm" aria-label="Filter by service">
            <option value="">All services</option>
            @foreach ($services as $service)
                <option value="{{ $service->id }}">{{ $service->name }}</option>
            @endforeach
        </select>
        <label class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-line px-3 text-sm">
            <input type="checkbox" wire:model.live="showArchived" class="h-4 w-4"> Archived
        </label>
    </div>

    @if ($canAssign && count($selected) > 0)
        <div class="mt-3 flex flex-wrap items-center gap-2 rounded-lg border border-line bg-paper-3 p-3">
            <span class="text-sm font-medium">{{ count($selected) }} selected</span>
            <select id="bulk-assign-user" class="min-h-[40px] rounded-lg border border-line bg-paper px-2 text-sm">
                <option value="">Assign to…</option>
                @foreach ($consultants as $consultant)
                    <option value="{{ $consultant->id }}">{{ $consultant->name }}</option>
                @endforeach
            </select>
            <button type="button" wire:click="bulkAssign(document.getElementById('bulk-assign-user').value)"
                    class="inline-flex min-h-[40px] items-center rounded-lg bg-brand px-3 text-sm font-semibold text-brand-ink">
                Assign
            </button>
            <button type="button" wire:click="bulkArchive" wire:confirm="Archive the selected leads?"
                    class="inline-flex min-h-[40px] items-center rounded-lg border border-line px-3 text-sm">
                Archive
            </button>
        </div>
    @endif

    <div class="mt-4 overflow-hidden rounded-xl border border-line bg-paper-2">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-muted">
                    @if ($canAssign) <th class="w-10 px-3 py-3"><span class="sr-only">Select</span></th> @endif
                    <th class="px-3 py-3">Lead</th>
                    <th class="hidden px-3 py-3 md:table-cell">Source</th>
                    <th class="px-3 py-3">Status</th>
                    <th class="px-3 py-3">SLA</th>
                    <th class="hidden px-3 py-3 lg:table-cell">Assigned</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leads as $lead)
                    <tr class="border-b border-line last:border-0 hover:bg-paper-3/60">
                        @if ($canAssign)
                            <td class="px-3 py-3">
                                <input type="checkbox" wire:model.live="selected" value="{{ $lead->id }}" class="h-4 w-4" aria-label="Select {{ $lead->name }}">
                            </td>
                        @endif
                        <td class="px-3 py-3">
                            <a href="{{ route('admin.leads.show', ['lead' => $lead->id]) }}" class="font-medium text-ink hover:text-brand">
                                {{ $lead->name }}@unless($canSeePii) <span class="text-xs text-ink-muted">(PII hidden)</span>@endunless
                            </a>
                            <div class="mt-0.5 text-xs text-ink-muted">
                                @if ($canSeePii) {{ $lead->email }} @if($lead->phone) · {{ $lead->phone }} @endif @else {{ $lead->type->label() }} @endif
                                @if ($lead->service) · {{ $lead->service->name }} @endif
                            </div>
                            @if ($lead->merged_into_lead_id)
                                <span class="mt-1 inline-flex items-center rounded-full border border-line px-2 py-0.5 text-[11px] font-medium text-ink-muted">Possible duplicate — review</span>
                            @endif
                        </td>
                        <td class="hidden px-3 py-3 text-xs text-ink-muted md:table-cell">{{ $lead->source->label() }} · {{ $lead->locale }}</td>
                        <td class="px-3 py-3">
                            <span class="inline-flex rounded-full border border-line px-2.5 py-1 text-xs font-medium">{{ $lead->status->label() }}</span>
                        </td>
                        <td class="px-3 py-3">
                            @php($sla = $lead->slaState())
                            <span @class([
                                'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                'bg-brand/15 text-brand' => $sla === 'ok' || $sla === 'done',
                                'bg-warning/15 text-warning' => $sla === 'warning',
                                'bg-danger-500/15 text-danger-500' => $sla === 'breached',
                            ])>{{ $lead->slaLabel() }}</span>
                        </td>
                        <td class="hidden px-3 py-3 text-xs text-ink-muted lg:table-cell">{{ $lead->assignedTo?->name ?? '—' }}</td>
                        <td class="px-3 py-3 text-end">
                            @if ($showArchived && $canAssign)
                                <button type="button" wire:click="unarchive('{{ $lead->id }}')" class="text-xs font-medium text-brand hover:underline">Restore</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-10 text-center text-sm text-ink-muted">
                        No leads match. The inbox fills itself the moment a form lands.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $leads->links() }}</div>
</div>
