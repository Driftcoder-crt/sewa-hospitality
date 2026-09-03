{{-- Lead detail (03-leads-crm §4.2): submission + status machine +
    timeline. PII gated by leads.pii.view. --}}
<div class="mx-auto max-w-5xl">
    <a href="{{ route('admin.leads') }}" class="text-sm font-medium text-brand hover:underline">← Inbox</a>

    <div class="mt-3 grid gap-5 lg:grid-cols-3">
        {{-- Left: submission + actions --}}
        <div class="flex flex-col gap-5 lg:col-span-2">
            <div class="rounded-xl border border-line bg-paper-2 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="font-display text-2xl">{{ $lead->name }}</h1>
                        <p class="mt-1 text-sm text-ink-muted">
                            {{ $lead->type->label() }} via {{ $lead->source->label() }} ·
                            {{ $lead->created_at->format('d M Y H:i') }} ·
                            {{ $lead->sla_due_at?->format('d M H:i') ?? 'no SLA' }} due
                        </p>
                    </div>
                    <span class="inline-flex rounded-full border border-line px-3 py-1 text-xs font-semibold">{{ $lead->status->label() }}</span>
                </div>

                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm md:grid-cols-3">
                    <div><dt class="text-xs uppercase text-ink-muted">Email</dt><dd class="mt-0.5 break-all">@if ($canSeePii) {{ $lead->email }} @else <span class="text-ink-muted">pii.view required</span> @endif</dd></div>
                    <div><dt class="text-xs uppercase text-ink-muted">Phone</dt><dd class="mt-0.5">@if ($canSeePii) {{ $lead->phone ?? '—' }} @else <span class="text-ink-muted">pii.view required</span> @endif</dd></div>
                    <div><dt class="text-xs uppercase text-ink-muted">Company</dt><dd class="mt-0.5">{{ $lead->company ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase text-ink-muted">Service</dt><dd class="mt-0.5">{{ $lead->service?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase text-ink-muted">City</dt><dd class="mt-0.5">{{ $lead->city?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase text-ink-muted">Locale</dt><dd class="mt-0.5">{{ $lead->locale }} · score {{ $lead->score }}</dd></div>
                </dl>

                @if ($lead->message)
                    <p class="mt-4 whitespace-pre-wrap rounded-lg border-s-4 border-brand bg-paper-3 p-3 text-sm">{{ $lead->message }}</p>
                @endif

                @if ($lead->merged_into_lead_id)
                    <p class="mt-3 rounded-lg border border-warning/40 bg-warning/10 p-3 text-sm">
                        Possible duplicate of another lead (same email + phone within 48h). Review and archive this row if confirmed.
                    </p>
                @endif
            </div>

            {{-- Status machine --}}
            <div class="rounded-xl border border-line bg-paper-2 p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Status machine</h2>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="status" class="text-sm font-medium">Move to</label>
                        <select id="status" wire:model.live="status" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status') <p class="mt-1 text-xs text-danger-500" role="alert">{{ $message }}</p> @enderror
                    </div>
                    @if ($status === 'lost')
                        <div>
                            <label for="lost-reason" class="text-sm font-medium">Lost reason (required)</label>
                            <select id="lost-reason" wire:model.live="lostReason" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                                <option value="">Select…</option>
                                @foreach ($lostReasons as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('lost_reason') <p class="mt-1 text-xs text-danger-500" role="alert">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    @if ($status === 'won')
                        <div>
                            <label for="deal-ref" class="text-sm font-medium">Organization link / quote ref (required)</label>
                            <input id="deal-ref" type="text" wire:model.live="dealReference" placeholder="SEWA-Q-2026-0001 or org name"
                                   class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                            @error('status') <p class="mt-1 text-xs text-danger-500" role="alert">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>
                <button type="button" wire:click="changeStatus" wire:loading.attr="disabled" wire:target="changeStatus"
                        class="mt-4 inline-flex min-h-[44px] items-center rounded-lg bg-brand px-4 text-sm font-semibold text-brand-ink">
                    Apply transition
                </button>
                <p class="mt-2 text-xs text-ink-muted">First move off “New” stamps first response — the SLA clock stops there.</p>
            </div>

            {{-- Add note --}}
            <div class="rounded-xl border border-line bg-paper-2 p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Add to timeline</h2>
                <textarea wire:model.live.debounce.300ms="note" rows="3" placeholder="Call summary, next step, context…"
                          class="mt-3 w-full rounded-lg border border-line bg-paper px-3 py-2.5 text-sm"></textarea>
                @error('note') <p class="mt-1 text-xs text-danger-500" role="alert">{{ $message }}</p> @enderror
                <button type="button" wire:click="addNote" wire:loading.attr="disabled" wire:target="addNote"
                        class="mt-3 inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm font-medium hover:bg-paper-3">
                    Add note
                </button>
            </div>
        </div>

        {{-- Right: assignment + next action --}}
        <div class="flex flex-col gap-5">
            <div class="rounded-xl border border-line bg-paper-2 p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Assignment</h2>
                @if ($canAssign)
                    <select wire:model.live="assignedUserId" class="mt-3 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                        <option value="">Unassigned</option>
                        @foreach ($consultants as $consultant)
                            <option value="{{ $consultant->id }}">{{ $consultant->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" wire:click="changeAssignment"
                            class="mt-3 inline-flex min-h-[44px] w-full items-center justify-center rounded-lg bg-brand px-4 text-sm font-semibold text-brand-ink">
                        Save assignment
                    </button>
                @else
                    <p class="mt-2 text-sm text-ink-soft">{{ $lead->assignedTo?->name ?? 'Unassigned' }} (assignment changes need the assign permission)</p>
                @endif
            </div>

            <div class="rounded-xl border border-line bg-paper-2 p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Next action</h2>
                <input type="datetime-local" wire:model.live="nextActionAt"
                       class="mt-3 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                @error('nextActionAt') <p class="mt-1 text-xs text-danger-500" role="alert">{{ $message }}</p> @enderror
                <button type="button" wire:click="changeNextAction"
                        class="mt-3 inline-flex min-h-[44px] w-full items-center justify-center rounded-lg border border-line px-4 text-sm font-medium hover:bg-paper-3">
                    Save
                </button>
            </div>
        </div>
    </div>

    {{-- Timeline --}}
    <div class="mt-5 rounded-xl border border-line bg-paper-2 p-5">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Timeline</h2>
        <ol class="mt-4 flex flex-col gap-4">
            @forelse ($events as $event)
                <li class="flex gap-3">
                    <span class="mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full bg-brand" aria-hidden="true"></span>
                    <div class="min-w-0">
                        <p class="text-sm">
                            <span class="font-semibold">{{ $event->type->label() }}</span>
                            <span class="text-ink-muted">· {{ $event->created_at->format('d M Y H:i') }} · {{ $event->user?->name ?? 'system' }}</span>
                        </p>
                        @if ($event->payload)
                            <p class="mt-0.5 break-words text-sm text-ink-soft">
                                @if (isset($event->payload['note'])) {{ $event->payload['note'] }}
                                @elseif (isset($event->payload['from'])) {{ $event->payload['from'] }} → {{ $event->payload['to'] }}
                                @elseif (isset($event->payload['message'])) {{ $event->payload['message'] }}
                                @else {{ collect($event->payload)->map(fn ($v, $k) => $k.': '.(is_string($v) ? $v : json_encode($v)))->implode(' · ') }} @endif
                            </p>
                        @endif
                    </div>
                </li>
            @empty
                <li class="text-sm text-ink-muted">No events yet.</li>
            @endforelse
        </ol>
    </div>
</div>
