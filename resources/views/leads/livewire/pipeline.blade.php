{{-- Pipeline kanban (03-leads-crm §4.3) — drag AND keyboard select per
     card (a11y: dragging is never the only path). --}}
<div>
    <h1 class="font-display text-2xl">Pipeline</h1>
    <p class="mt-1 text-sm text-ink-muted">Drag cards between stages, or use each card's select. Every move is logged.</p>

    @error('pipeline')
        <p class="mt-3 rounded-lg border border-danger-500/40 bg-danger-500/10 p-3 text-sm text-danger-500" role="alert">{{ $message }}</p>
    @enderror

    <div class="mt-4 flex gap-4 overflow-x-auto pb-4" x-data="{ dragOver: null }">
        @foreach ($columns as $column)
            @php($statusValue = $column['status']->value)
            <section class="flex w-72 shrink-0 flex-col rounded-xl border border-line bg-paper-2"
                     aria-label="Stage: {{ $column['status']->label() }}"
                     x-data
                     @dragover.prevent="dragOver = '{{ $statusValue }}'"
                     @drop.prevent="$wire.moveLead(event.dataTransfer.getData('text/plain'), '{{ $statusValue }}'); dragOver = null"
                     @class(['ring-2 ring-brand/40' => false])>
                <header class="flex items-center justify-between border-b border-line px-4 py-3">
                    <h2 class="text-sm font-semibold">{{ $column['status']->label() }}</h2>
                    <span class="rounded-full bg-paper-3 px-2 py-0.5 text-xs text-ink-muted">{{ $column['leads']->count() }}</span>
                </header>

                <div class="flex flex-1 flex-col gap-2 p-3">
                    @forelse ($column['leads'] as $lead)
                        <article class="rounded-lg border border-line bg-paper p-3 @if(false) ring-2 ring-brand/40 @endif"
                                 draggable="true"
                                 @dragstart="event.dataTransfer.setData('text/plain', '{{ $lead->id }}')"
                                 x-data="{ stage: '{{ $lead->status->value }}' }">
                            <a href="{{ route('admin.leads.show', ['lead' => $lead->id]) }}" class="text-sm font-medium hover:text-brand">
                                {{ str($lead->name)->limit(30) }}
                            </a>
                            <p class="mt-0.5 text-xs text-ink-muted">
                                {{ $lead->service?->name ?? $lead->source->label() }} · score {{ $lead->score }}
                            </p>
                            <label class="mt-2 block">
                                <span class="sr-only">Move {{ $lead->name }} to stage</span>
                                <select x-model="stage"
                                        @change="$wire.moveLead('{{ $lead->id }}', stage)"
                                        class="min-h-[36px] w-full rounded-md border border-line bg-paper-2 px-2 text-xs">
                                    @foreach (\App\Modules\Leads\Enums\LeadStatus::options() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </article>
                    @empty
                        <p class="rounded-lg border border-dashed border-line px-3 py-6 text-center text-xs text-ink-muted">Empty stage</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</div>
