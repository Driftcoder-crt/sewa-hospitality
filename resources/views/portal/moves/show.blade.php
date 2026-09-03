@extends('layouts.portal')

@section('title', 'Move '.$move->reference.' — Sewa Hospitality Portal')

@section('content')
    <div class="flex flex-col gap-8">
        {{-- Header + consultant card --}}
        <section class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="eyebrow text-ink-muted">{{ $move->organization?->name }} · {{ $move->reference }}</p>
                <h1 class="mt-1 font-display text-3xl">
                    {{ $move->employee?->name ?? $move->assignee_name ?? 'Your move' }}
                    @if ($move->origin_city || $move->destinationCity)
                        <span class="text-ink-soft">· {{ $move->origin_city }} → {{ $move->destinationCity?->name }}</span>
                    @endif
                </h1>
                <p class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                    <span class="rounded-full bg-brand/10 px-3 py-1 text-xs font-semibold text-brand">{{ $move->stage->label() }}</span>
                    <span class="rounded-full bg-paper-3 px-3 py-1 text-xs font-medium text-ink-soft">{{ $move->status->label() }}</span>
                    @if ($move->move_date)
                        <span class="text-ink-muted">Moving {{ $move->move_date->format('d M Y') }}</span>
                    @endif
                </p>
            </div>

            <aside class="w-full max-w-xs rounded-xl border border-line bg-paper-2 p-5" aria-label="Assigned consultant">
                <p class="eyebrow text-ink-muted">Your consultant</p>
                @if ($move->consultant)
                    <p class="mt-2 font-display text-lg">{{ $move->consultant->name }}</p>
                    <p class="text-sm text-ink-soft">{{ $move->consultant->email }}</p>
                    <a href="{{ route('portal.messages') }}" class="mt-3 inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">
                        Message the team
                    </a>
                @elseif ($move->assignee_name)
                    <p class="mt-2 font-display text-lg">{{ $move->assignee_name }}</p>
                    <p class="text-sm text-ink-soft">{{ $move->assignee_email }}</p>
                @else
                    <p class="mt-2 text-sm text-ink-soft">A consultant is being assigned — you will be notified.</p>
                @endif
            </aside>
        </section>

        {{-- Timeline (stage progress) --}}
        <section aria-label="Move timeline" class="rounded-xl border border-line bg-paper-2 p-5">
            <h2 class="font-display text-lg">Timeline</h2>
            <ol class="mt-4 grid gap-3 sm:grid-cols-3 lg:grid-cols-6" role="list">
                @foreach (\App\Modules\Portal\Enums\MoveStage::pipeline() as $stage)
                    @php($done = $move->stage->position() >= $stage->position())
                    <li class="flex items-center gap-3 sm:flex-col sm:items-start">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $done ? 'bg-brand text-white' : 'bg-paper-3 text-ink-muted' }}"
                              aria-hidden="true">{{ $done ? '✓' : $stage->position() + 1 }}</span>
                        <div>
                            <p class="text-sm font-medium {{ $done ? 'text-ink' : 'text-ink-muted' }}">{{ $stage->label() }}</p>
                            @if ($move->stage === $stage)
                                <p class="text-xs text-brand">current</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Services included --}}
        @if (! empty($move->service_ids))
            <section aria-label="Services included">
                <h2 class="font-display text-lg">Services included</h2>
                <ul class="mt-3 flex flex-wrap gap-2" role="list">
                    @foreach ($move->service_ids as $serviceId)
                        @php($service = \App\Modules\Services\Models\Service::find($serviceId))
                        @if ($service)
                            <li class="rounded-full border border-line bg-paper-2 px-4 py-1.5 text-sm text-ink-soft">{{ $service->name }}</li>
                        @endif
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Checklist --}}
        <section aria-label="Checklist" class="rounded-xl border border-line bg-paper-2">
            <div class="flex items-center justify-between border-b border-line p-5">
                <h2 class="font-display text-lg">Checklist</h2>
                <a href="{{ route('portal.documents', $move) }}" class="text-sm font-medium text-brand hover:underline">
                    Documents ({{ $documentsCount }})
                </a>
            </div>
            <ul role="list">
                @forelse ($checklist as $item)
                    <li class="flex items-start justify-between gap-4 border-b border-line p-5 last:border-0">
                        <div>
                            <p class="text-sm font-medium {{ $item->status->value === 'done' ? 'text-ink-muted line-through' : '' }}">{{ $item->title }}</p>
                            @if ($item->detail)
                                <p class="mt-1 text-sm text-ink-soft">{{ $item->detail }}</p>
                            @endif
                            @if ($item->done_at)
                                <p class="mt-1 text-xs text-ink-muted">Done {{ $item->done_at->format('d M Y') }}@if($item->doneBy) by {{ $item->doneBy->name }} @endif</p>
                            @endif
                        </div>
                        <div class="text-right">
                            @if ($item->due_at)
                                <p class="text-xs {{ $item->isOverdue() ? 'font-semibold text-danger' : 'text-ink-muted' }}">
                                    Due {{ $item->due_at->format('d M') }}{{ $item->isOverdue() ? ' · overdue' : '' }}
                                </p>
                            @endif
                            <span class="mt-1 inline-block rounded-full px-3 py-0.5 text-xs font-semibold {{ $item->status->value === 'done' ? 'bg-brand/10 text-brand' : 'bg-paper-3 text-ink-soft' }}">
                                {{ $item->status->value === 'done' ? 'Done' : 'Pending' }}
                            </span>
                        </div>
                    </li>
                @empty
                    <li class="p-5 text-sm text-ink-soft">The checklist is being prepared.</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
