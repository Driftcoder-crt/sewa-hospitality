@extends('layouts.portal')

@section('title', 'Dashboard — Sewa Hospitality Portal')

@section('content')
    <div class="flex flex-col gap-8">
        {{-- Greeting band --}}
        <section class="flex flex-col gap-1">
            <p class="eyebrow text-ink-muted">Welcome back</p>
            <h1 class="font-display text-3xl">{{ auth()->user()->name }}</h1>
            <p class="text-sm text-ink-soft">{{ app(\App\Modules\Portal\Services\PortalContext::class)->isOrgWide() ? app(\App\Modules\Portal\Services\PortalContext::class)->organization()->name.' — organization view' : 'Your personal move view' }}</p>
        </section>

        {{-- Move summary cards --}}
        <section aria-label="Your moves">
            <h2 class="font-display text-lg">Moves</h2>
            @forelse ($moves as $move)
                <a href="{{ route('portal.moves.show', $move) }}"
                   class="mt-3 flex flex-col gap-4 rounded-xl border border-line bg-paper-2 p-5 transition hover:border-brand/40 hover:shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="eyebrow text-ink-muted">{{ $move->reference }} · {{ $move->destinationCity?->name ?? $move->origin_city }}</p>
                        <p class="mt-1 font-medium">{{ $move->stage->label() }}
                            <span class="text-ink-muted">· {{ $move->pending_count }} open task{{ $move->pending_count === 1 ? '' : 's' }}</span>
                        </p>
                        @if ($move->move_date)
                            <p class="mt-1 text-sm text-ink-soft">Move date {{ $move->move_date->format('d M Y') }}</p>
                        @endif
                    </div>
                    <div class="w-full sm:w-48">
                        <div class="h-1.5 overflow-hidden rounded-full bg-paper-3" role="presentation">
                            <div class="h-full rounded-full bg-brand" style="width: {{ $move->stageProgress() }}%"></div>
                        </div>
                        <p class="mt-1 text-right text-xs text-ink-muted">{{ $move->stageProgress() }}%</p>
                    </div>
                </a>
            @empty
                <p class="mt-3 rounded-xl border border-dashed border-line bg-paper-2 p-6 text-sm text-ink-soft">
                    No moves on record yet. Your consultant team will set one up and it appears here instantly.
                </p>
            @endforelse
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Next tasks --}}
            <section aria-label="Next checklist items" class="rounded-xl border border-line bg-paper-2 p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-lg">Next up on your checklist</h2>
                </div>
                @forelse ($nextTasks as $task)
                    <div class="mt-3 flex items-start justify-between gap-3 border-b border-line pb-3 last:border-0 last:pb-0">
                        <div>
                            <p class="text-sm font-medium">{{ $task->title }}</p>
                            @if ($task->due_at)
                                <p class="mt-0.5 text-xs {{ \Illuminate\Support\Carbon::parse($task->due_at)->isPast() ? 'text-danger' : 'text-ink-muted' }}">
                                    Due {{ \Illuminate\Support\Carbon::parse($task->due_at)->format('d M') }}
                                    {{ \Illuminate\Support\Carbon::parse($task->due_at)->isPast() ? '· overdue' : '' }}
                                </p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="mt-3 text-sm text-ink-soft">Nothing pending — beautifully on track.</p>
                @endforelse
            </section>

            {{-- Latest documents --}}
            <section aria-label="Latest documents" class="rounded-xl border border-line bg-paper-2 p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-lg">Latest documents</h2>
                    @if ($moves->isNotEmpty())
                        <a href="{{ route('portal.moves.show', $moves->first()) }}" class="text-sm font-medium text-brand hover:underline">View all</a>
                    @endif
                </div>
                @forelse ($latestDocuments as $document)
                    <div class="mt-3 flex items-start justify-between gap-3 border-b border-line pb-3 last:border-0 last:pb-0">
                        <div>
                            <p class="text-sm font-medium">{{ $document->title }}</p>
                            <p class="mt-0.5 text-xs text-ink-muted">{{ $document->category?->label() }} · {{ $document->move?->reference }}</p>
                        </div>
                        <span class="text-xs text-ink-muted">{{ $document->created_at->format('d M') }}</span>
                    </div>
                @empty
                    <p class="mt-3 text-sm text-ink-soft">No documents shared yet.</p>
                @endforelse
            </section>

            {{-- Unread messages --}}
            <section aria-label="Unread messages" class="rounded-xl border border-line bg-paper-2 p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-lg">Your consultant chat</h2>
                    <a href="{{ route('portal.messages') }}" class="text-sm font-medium text-brand hover:underline">Open messages</a>
                </div>
                @forelse ($unreadThreads as $thread)
                    <a href="{{ route('portal.messages.show', $thread) }}" class="mt-3 flex items-start justify-between gap-3 border-b border-line pb-3 last:border-0 last:pb-0 hover:opacity-80">
                        <div>
                            <p class="text-sm font-medium">{{ $thread->subject ?? 'Conversation' }}</p>
                            <p class="mt-0.5 text-xs text-ink-muted">{{ $thread->move?->reference }}</p>
                        </div>
                        <span class="rounded-full bg-brand/10 px-2 py-0.5 text-xs font-semibold text-brand">New</span>
                    </a>
                @empty
                    <p class="mt-3 text-sm text-ink-soft">No unread replies — your consultant team is on it.</p>
                @endforelse
            </section>

            {{-- Recent notifications --}}
            <section aria-label="Recent notifications" class="rounded-xl border border-line bg-paper-2 p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-lg">Notifications</h2>
                    <a href="{{ route('portal.notifications') }}" class="text-sm font-medium text-brand hover:underline">See all</a>
                </div>
                @forelse ($notifications as $notification)
                    <div class="mt-3 border-b border-line pb-3 last:border-0 last:pb-0">
                        <p class="text-sm {{ $notification->read_at ? 'text-ink-soft' : 'font-medium' }}">{{ $notification->title }}</p>
                        <p class="mt-0.5 text-xs text-ink-muted">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                @empty
                    <p class="mt-3 text-sm text-ink-soft">Nothing yet — updates land here as your move progresses.</p>
                @endforelse
            </section>
        </div>
    </div>
@endsection
