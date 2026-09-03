{{-- Job editor (06-hr §4.1): content sections + settings + live preview. --}}
<div class="mx-auto max-w-6xl">
    <a href="{{ route('admin.jobs') }}" class="text-sm font-medium text-brand hover:underline">← Job postings</a>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
        <h1 class="font-display text-2xl">{{ $job->title }}</h1>
        <div class="flex items-center gap-2">
            <span class="inline-flex rounded-full border border-line px-3 py-1 text-xs font-semibold">{{ $job->status->label() }}</span>
            @if ($job->status->value !== 'draft')
                <a href="{{ $job->publicPath() }}" class="text-sm font-medium text-brand hover:underline" target="_blank" rel="noopener">View public page ↗</a>
            @endif
        </div>
    </div>

    <div class="mt-4 grid gap-5 lg:grid-cols-3">
        <div class="flex flex-col gap-5 lg:col-span-2">
            <div class="rounded-xl border border-line bg-paper-2 p-5">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="je-title" class="text-sm font-medium">Title</label>
                        <input id="je-title" type="text" wire:model.live.debounce.300ms="title" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                    </div>
                    <div>
                        <label for="je-dept" class="text-sm font-medium">Department</label>
                        <select id="je-dept" wire:model.live="department" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                            @foreach ($departments as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="je-city" class="text-sm font-medium">City</label>
                        <select id="je-city" wire:model.live="locationCityId" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                            <option value="">—</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city->id }}">{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="je-loc" class="text-sm font-medium">Location text</label>
                        <input id="je-loc" type="text" wire:model.live.debounce.300ms="locationText" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                    </div>
                    <div>
                        <label for="je-type" class="text-sm font-medium">Employment type</label>
                        <select id="je-type" wire:model.live="employmentType" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                            @foreach ($employmentTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="je-exp" class="text-sm font-medium">Experience (min–max years)</label>
                        <div class="mt-1 flex gap-2">
                            <input id="je-exp" type="number" min="0" max="40" wire:model.live.debounce.300ms="experienceMin" placeholder="min" class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                            <input type="number" min="0" max="40" wire:model.live.debounce.300ms="experienceMax" placeholder="max" class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                        </div>
                    </div>
                    <div>
                        <label for="je-closes" class="text-sm font-medium">Applications close</label>
                        <input id="je-closes" type="date" wire:model.live="closesAt" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                    </div>
                    <div>
                        <label for="je-email" class="text-sm font-medium">Applications email override</label>
                        <input id="je-email" type="email" wire:model.live.debounce.300ms="appliesToEmail" placeholder="careers@sewahospitality.com" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                    </div>
                </div>
            </div>

            @foreach ([['descriptionHtml', 'Description (HTML)'], ['responsibilitiesHtml', 'Responsibilities (HTML)'], ['skillsHtml', 'Skills & qualifications (HTML)']] as [$field, $label])
                <div class="rounded-xl border border-line bg-paper-2 p-5">
                    <label for="je-{{ $field }}" class="text-sm font-medium">{{ $label }}</label>
                    <textarea id="je-{{ $field }}" wire:model.live.debounce.500ms="{{ $field }}" rows="6"
                              class="mt-2 w-full rounded-lg border border-line bg-paper px-3 py-2.5 font-mono text-xs"></textarea>
                    @error($field) <p class="mt-1 text-xs text-danger-500">{{ $message }}</p> @enderror
                    <details class="mt-2">
                        <summary class="cursor-pointer text-xs text-ink-muted">Preview rendered content</summary>
                        <div class="prose mt-2 max-w-none rounded-lg border border-line bg-paper p-3 text-sm">{!! $$field !!}</div>
                    </details>
                </div>
            @endforeach

            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                    class="inline-flex min-h-[44px] w-full items-center justify-center rounded-lg bg-brand text-sm font-semibold text-brand-ink sm:w-48">
                Save role
            </button>
        </div>

        <aside class="rounded-xl border border-line bg-paper-2 p-5 lg:sticky lg:top-20 lg:self-start">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Status machine</h2>
            <ol class="mt-3 flex flex-col gap-2 text-sm">
                @foreach (['draft', 'open', 'paused', 'closed'] as $stage)
                    <li class="flex items-center gap-2">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full {{ $job->status->value === $stage ? 'bg-brand' : 'bg-line' }}"></span>
                        <span class="{{ $job->status->value === $stage ? 'font-semibold text-ink' : 'text-ink-muted' }}">{{ ucfirst($stage) }}</span>
                    </li>
                @endforeach
            </ol>
            <p class="mt-4 text-xs text-ink-muted">Open/pause/close from the postings table. Closed keeps the URL with a "see similar" state — the reference's 404 job pages are structurally impossible here.</p>
        </aside>
    </div>
</div>
