{{-- ATS pipeline (06-hr §4.2) — kanban + resume preview + notes +
    rating + duplicate detection. --}}
<div>
    <h1 class="font-display text-2xl">Applications</h1>
    <p class="mt-1 text-sm text-ink-muted">new → screening → shortlisted → interview → offer → hired · rejected/withdrawn terminal · status emails fire per catalog.</p>

    <div class="mt-4 flex items-center gap-2">
        <select wire:model.live="jobFilter" class="min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm" aria-label="Filter by job">
            <option value="">All roles</option>
            @foreach ($jobs as $job)
                <option value="{{ $job->id }}">{{ $job->title }}</option>
            @endforeach
        </select>
    </div>

    @if ($duplicateEmails->isNotEmpty())
        <p class="mt-3 rounded-lg border border-warning/40 bg-warning/10 p-3 text-sm">
            Duplicate applicants: @foreach ($duplicateEmails as $email => $count)<strong>{{ $email }}</strong> ({{ $count }})@if(! $loop->last) · @endif@endforeach
        </p>
    @endif

    @error('pipeline')
        <p class="mt-3 rounded-lg border border-danger-500/40 bg-danger-500/10 p-3 text-sm text-danger-500" role="alert">{{ $message }}</p>
    @enderror

    <div class="mt-4 flex gap-4 overflow-x-auto pb-4">
        @foreach ($columns as $column)
            @php($statusValue = $column['status']->value)
            <section class="flex w-80 shrink-0 flex-col rounded-xl border border-line bg-paper-2"
                     aria-label="Stage: {{ $column['status']->label() }}"
                     @dragover.prevent
                     @drop.prevent="$wire.moveApplication(event.dataTransfer.getData('text/plain'), '{{ $statusValue }}')">
                <header class="flex items-center justify-between border-b border-line px-4 py-3">
                    <h2 class="text-sm font-semibold">{{ $column['status']->label() }}</h2>
                    <span class="rounded-full bg-paper-3 px-2 py-0.5 text-xs text-ink-muted">{{ $column['applications']->count() }}</span>
                </header>

                <div class="flex flex-1 flex-col gap-2 p-3">
                    @forelse ($column['applications'] as $application)
                        <article class="rounded-lg border border-line bg-paper p-3" draggable="true"
                                 x-data="{ stage: '{{ $statusValue }}' }"
                                 @dragstart="event.dataTransfer.setData('text/plain', '{{ $application->id }}')">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">{{ $application->applicant_name }}</p>
                                    @if ($canSeePii)
                                        <p class="truncate text-xs text-ink-muted">{{ $application->applicant_email }}</p>
                                    @endif
                                </div>
                                @if ($application->rating)
                                    <span class="shrink-0 text-xs font-semibold text-brand">{{ $application->rating }}/5</span>
                                @endif
                            </div>

                            <p class="mt-1 truncate text-xs text-ink-muted">{{ $application->posting?->title ?? 'General' }} · {{ $application->created_at->format('d M') }}</p>

                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                @if ($canSeePii && $application->resume_path)
                                    <a href="{{ route('admin.applications.resume', ['application' => $application->id]) }}"
                                       class="rounded-full border border-line px-2.5 py-1 font-medium hover:bg-paper-3" download>Resume ↓</a>
                                @endif
                                <button type="button" wire:click="openNote('{{ $application->id }}')"
                                        class="rounded-full border border-line px-2.5 py-1 font-medium hover:bg-paper-3">Note</button>
                            </div>

                            <label class="mt-2 block">
                                <span class="sr-only">Move {{ $application->applicant_name }} to stage</span>
                                <select x-model="stage" @change="$wire.moveApplication('{{ $application->id }}', stage)"
                                        class="min-h-[36px] w-full rounded-md border border-line bg-paper-2 px-2 text-xs">
                                    @foreach (\App\Modules\Careers\Enums\ApplicationStatus::options() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <div class="mt-2 flex items-center gap-1" role="group" aria-label="Rate candidate">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button type="button" wire:click="rate('{{ $application->id }}', {{ $i }})"
                                            @class(['text-brand' => $application->rating >= $i, 'text-ink-muted/40' => $application->rating < $i])
                                            class="text-base leading-none hover:opacity-75" aria-label="Rate {{ $i }}">★</button>
                                @endfor
                            </div>

                            @if ($this->noteTarget === $application->id)
                                <div class="mt-2">
                                    <textarea wire:model.live.debounce.300ms="noteText" rows="2" placeholder="Interview notes…"
                                              class="w-full rounded-md border border-line bg-paper px-2 py-1.5 text-xs"></textarea>
                                    @error('noteText') <p class="text-xs text-danger-500">{{ $message }}</p> @enderror
                                    <button type="button" wire:click="addNote" class="mt-1 text-xs font-semibold text-brand hover:underline">Save note</button>
                                </div>
                            @endif
                        </article>
                    @empty
                        <p class="rounded-lg border border-dashed border-line px-3 py-6 text-center text-xs text-ink-muted">Empty</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</div>
