@extends('layouts.portal')

@section('title', 'Moves — Sewa Hospitality Portal')

@section('content')
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-1">
            <p class="eyebrow text-ink-muted">Client portal</p>
            <h1 class="font-display text-3xl">Moves</h1>
        </div>

        {{-- Stage distribution (the corporate mobility-team view, 04 doc §5) --}}
        @if ($stageDistribution->isNotEmpty())
            <section aria-label="Stage overview" class="rounded-xl border border-line bg-paper-2 p-5">
                <div class="flex flex-wrap gap-x-6 gap-y-2">
                    @foreach (\App\Modules\Portal\Enums\MoveStage::pipeline() as $stage)
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 rounded-full {{ $stage === \App\Modules\Portal\Enums\MoveStage::Closed ? 'bg-ink-muted' : 'bg-brand' }}"></span>
                            <span class="text-sm text-ink-soft">{{ $stage->label() }}</span>
                            <span class="text-sm font-semibold">{{ $stageDistribution->get($stage->value, 0) }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section aria-label="All moves" class="flex flex-col gap-3">
            @forelse ($moves as $move)
                <a href="{{ route('portal.moves.show', $move) }}"
                   class="flex flex-col gap-4 rounded-xl border border-line bg-paper-2 p-5 transition hover:border-brand/40 hover:shadow-sm md:flex-row md:items-center md:justify-between">
                    <div class="min-w-0">
                        <p class="eyebrow text-ink-muted">{{ $move->reference }}</p>
                        <p class="mt-1 truncate font-medium">
                            {{ $move->employee?->name ?? $move->assignee_name ?? 'Relocation' }}
                            <span class="text-ink-muted">· {{ $move->origin_city }} → {{ $move->destinationCity?->name ?? 'India' }}</span>
                        </p>
                        <p class="mt-1 text-sm text-ink-soft">
                            {{ $move->stage->label() }} · {{ $move->status->label() }}
                            @if ($move->move_date) · moving {{ $move->move_date->format('d M Y') }} @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-ink-muted">{{ $move->pending_count }} open task{{ $move->pending_count === 1 ? '' : 's' }}</span>
                        <div class="w-28">
                            <div class="h-1.5 overflow-hidden rounded-full bg-paper-3" role="presentation">
                                <div class="h-full rounded-full bg-brand" style="width: {{ $move->stageProgress() }}%"></div>
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <p class="rounded-xl border border-dashed border-line bg-paper-2 p-6 text-sm text-ink-soft">
                    No moves yet — your organization's relocations appear here the moment they are set up.
                </p>
            @endforelse
        </section>

        {{ $moves->links() }}
    </div>
@endsection
