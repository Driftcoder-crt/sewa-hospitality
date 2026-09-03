<div class="admin-screen">
@section('title', 'Move '.$move->reference.' — Sewa Admin')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <a href="{{ route('admin.moves') }}" wire:navigate class="text-sm text-brand hover:underline">← All moves</a>
            <h1 class="mt-1 font-display text-2xl text-ink">{{ $move->reference }} — {{ $move->employee?->name ?? $move->assignee_name }}</h1>
            <p class="eyebrow mt-1 text-ink-muted">{{ $move->organization?->name }} · stage: {{ $move->stage->label() }} · {{ $move->status->label() }}</p>
        </div>
        <a href="{{ route('portal.moves.show', $move) }}" target="_blank" rel="noopener"
           class="inline-flex min-h-[44px] items-center rounded-full border border-line px-4 text-sm font-semibold text-ink-soft hover:bg-paper-3">
            View in portal ↗
        </a>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        {{-- Details --}}
        <section class="xl:col-span-2 flex flex-col gap-6">
            <form wire:submit="saveDetails" class="rounded-xl border border-line bg-paper-2 p-5">
                <h2 class="font-display text-lg">Details</h2>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-ink-muted" for="assigneeName">Relocatee / assignee name</label>
                        <input id="assigneeName" type="text" wire:model="assigneeName" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        @error('assigneeName') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-ink-muted" for="assigneeEmail">Assignee email</label>
                        <input id="assigneeEmail" type="email" wire:model="assigneeEmail" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-ink-muted" for="employeeUserId">Portal employee account</label>
                        <select id="employeeUserId" wire:model="employeeUserId" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                            <option value="">None yet</option>
                            @foreach ($clientUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-ink-muted" for="consultantUserId">Primary consultant</label>
                        <select id="consultantUserId" wire:model="consultantUserId" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                            <option value="">Unassigned</option>
                            @foreach ($staffUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-ink-muted" for="originCity">Origin city</label>
                        <input id="originCity" type="text" wire:model="originCity" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-ink-muted" for="moveDate">Move date</label>
                        <input id="moveDate" type="date" wire:model="moveDate" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold text-ink-muted" for="summary">Summary notes (internal + portal)</label>
                        <textarea id="summary" rows="3" wire:model="summary" class="mt-1 w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm focus:border-brand focus:outline-none"></textarea>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-ink-muted" for="status">Operational status</label>
                        <select id="status" wire:model="status" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <fieldset class="rounded-lg border border-line p-3">
                            <legend class="px-1 text-xs font-semibold text-ink-muted">Services included</legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($services as $service)
                                    <label class="flex min-h-[44px] cursor-pointer items-center gap-2 rounded-full border border-line px-3 text-sm {{ in_array((string) $service->id, $serviceIds) ? 'border-brand bg-brand/10 text-brand' : 'text-ink-soft' }}">
                                        <input type="checkbox" wire:model="serviceIds" value="{{ $service->id }}" class="accent-brand">
                                        {{ $service->name }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>
                </div>
                <button type="submit" class="mt-4 inline-flex min-h-[44px] items-center rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink hover:opacity-90">Save details</button>
            </form>

            {{-- Checklist builder --}}
            <section class="rounded-xl border border-line bg-paper-2">
                <div class="border-b border-line p-5 pb-3">
                    <h2 class="font-display text-lg">Checklist ({{ $move->checklistItems->count() }})</h2>
                </div>
                <ul class="px-5" role="list">
                    @forelse ($move->checklistItems as $item)
                        <li class="flex items-center justify-between gap-3 border-b border-line py-3 last:border-0">
                            <div>
                                <p class="text-sm font-medium {{ $item->status->value === 'done' ? 'text-ink-muted line-through' : '' }}">{{ $item->title }}</p>
                                <p class="mt-0.5 text-xs text-ink-muted">
                                    {{ $item->due_at ? 'Due '.$item->due_at->format('d M') : 'No due date' }}
                                    @if ($item->done_at) · done {{ $item->done_at->format('d M') }} @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($item->status->value !== 'done')
                                    <button type="button" wire:click="markItemDone('{{ $item->id }}')" class="min-h-[44px] rounded-full border border-brand px-3 text-xs font-semibold text-brand hover:bg-brand hover:text-brand-ink">Mark done</button>
                                    <button type="button" wire:click="removeItem('{{ $item->id }}')" class="min-h-[44px] rounded-full px-3 text-xs font-medium text-ink-muted hover:text-danger">Remove</button>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-ink-soft">No tasks yet — add the first below or apply a template.</li>
                    @endforelse
                </ul>
                <form wire:submit="addChecklistItem" class="grid gap-3 border-t border-line p-5 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label class="sr-only" for="newItemTitle">Task title</label>
                        <input id="newItemTitle" type="text" wire:model="newItemTitle" placeholder="Task title" class="w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        @error('newItemTitle') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="sr-only" for="newItemDue">Due date</label>
                        <input id="newItemDue" type="date" wire:model="newItemDue" class="w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                    </div>
                    <button type="submit" class="inline-flex min-h-[44px] items-center justify-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">Add task</button>
                </form>
            </section>

            {{-- Documents --}}
            <section class="rounded-xl border border-line bg-paper-2">
                <div class="border-b border-line p-5 pb-3">
                    <h2 class="font-display text-lg">Documents ({{ $move->documents->count() }})</h2>
                    <p class="mt-1 text-xs text-ink-muted">Publishing notifies the employee. Files stay on the private disk — never public URLs.</p>
                </div>
                <ul class="px-5" role="list">
                    @forelse ($move->documents as $document)
                        <li class="flex items-center justify-between gap-3 border-b border-line py-3 last:border-0">
                            <div>
                                <p class="text-sm font-medium">{{ $document->title }}</p>
                                <p class="mt-0.5 text-xs text-ink-muted">
                                    {{ $document->category->label() }} · visible: {{ $document->visible_to->value }}
                                    @if ($document->expires_at) · expires {{ $document->expires_at->format('d M Y') }} @endif
                                </p>
                            </div>
                            <button type="button" wire:click="removeDocument('{{ $document->id }}')" class="min-h-[44px] rounded-full px-3 text-xs font-medium text-ink-muted hover:text-danger">Remove</button>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-ink-soft">No documents yet.</li>
                    @endforelse
                </ul>
                <form wire:submit="publishDocument" enctype="multipart/form-data" class="grid gap-3 border-t border-line p-5 md:grid-cols-3">
                    <div>
                        <label class="text-xs font-semibold text-ink-muted" for="documentTitle">Title</label>
                        <input id="documentTitle" type="text" wire:model="documentTitle" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        @error('documentTitle') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-ink-muted" for="documentCategory">Category</label>
                        <select id="documentCategory" wire:model="documentCategory" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                            @foreach ($categories as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-ink-muted" for="documentVisibility">Visible to</label>
                        <select id="documentVisibility" wire:model="documentVisibility" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                            @foreach ($visibilities as $visibility)
                                <option value="{{ $visibility->value }}">{{ ucfirst($visibility->value) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-ink-muted" for="documentFile">File (≤10 MB)</label>
                        <input id="documentFile" type="file" wire:model="documentFile"
                               accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                               class="mt-1 w-full min-h-[44px] text-sm file:mr-3 file:rounded-full file:border-0 file:bg-paper-3 file:px-4 file:py-2 file:text-xs file:font-semibold">
                        @error('documentFile') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-ink-muted" for="documentExpiry">Expires (visa/lease)</label>
                        <input id="documentExpiry" type="date" wire:model="documentExpiry" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="inline-flex min-h-[44px] w-full items-center justify-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">Upload & publish</button>
                    </div>
                </form>
            </section>
        </section>

        {{-- Right rail: stage machine + templates --}}
        <aside class="flex flex-col gap-6">
            <section class="rounded-xl border border-line bg-paper-2 p-5" aria-label="Stage machine">
                <h2 class="font-display text-lg">Stage machine</h2>
                <p class="mt-1 text-xs text-ink-muted">Only legal transitions are offered — the machine guards, the UI reflects it.</p>
                <div class="mt-3 flex flex-col gap-2">
                    @foreach ($stageOptions as $value => $label)
                        <div class="flex items-center justify-between rounded-lg border px-3 py-2 text-sm {{ $move->stage->value === $value ? 'border-brand bg-brand/10' : 'border-line' }}">
                            <span class="{{ $move->stage->value === $value ? 'font-semibold text-brand' : 'text-ink-soft' }}">{{ $label }}</span>
                            @if (in_array($value, $allowedTargets, true))
                                <button type="button" wire:click="advanceStage('{{ $value }}')" wire:confirm="Advance the stage? Employee and managers are notified."
                                        class="rounded-full bg-brand px-3 py-1 text-xs font-semibold text-brand-ink">Advance →</button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-xl border border-line bg-paper-2 p-5" aria-label="Templates">
                <h2 class="font-display text-lg">Templates</h2>
                <p class="mt-1 text-xs text-ink-muted">Service-combo presets that prefill services + checklist.</p>
                <div class="mt-3 flex flex-col gap-2">
                    <button type="button" wire:click="applyTemplate('standard-expat')" class="min-h-[44px] rounded-full border border-line px-4 text-left text-sm font-medium text-ink-soft hover:bg-paper-3">
                        Standard expat relocation — home, school, FRRO, settling-in
                    </button>
                    <button type="button" wire:click="applyTemplate('corporate-group')" class="min-h-[44px] rounded-full border border-line px-4 text-left text-sm font-medium text-ink-soft hover:bg-paper-3">
                        Corporate group move — home, FRRO, fleet, tenancy
                    </button>
                    <button type="button" wire:click="applyTemplate('quick-landing')" class="min-h-[44px] rounded-full border border-line px-4 text-left text-sm font-medium text-ink-soft hover:bg-paper-3">
                        Quick landing — meet & greet, fleet, home search
                    </button>
                </div>
            </section>
        </aside>
    </div>
</div>
