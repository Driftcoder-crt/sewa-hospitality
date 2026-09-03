@extends('layouts.portal')

@section('title', 'Messages — Sewa Hospitality Portal')

@section('content')
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-1">
            <p class="eyebrow text-ink-muted">Client portal</p>
            <h1 class="font-display text-3xl">Messages</h1>
            <p class="text-sm text-ink-soft">Direct thread with your consultant team — no email ping-pong, full context kept.</p>
        </div>

        <section aria-label="Threads" class="flex flex-col gap-3">
            @forelse ($threads as $thread)
                @php($last = $thread->lastMessage())
                <a href="{{ route('portal.messages.show', $thread) }}"
                   class="flex flex-col gap-2 rounded-xl border border-line bg-paper-2 p-5 transition hover:border-brand/40 hover:shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="font-medium">{{ $thread->subject ?? 'Conversation' }}</p>
                        <p class="mt-0.5 truncate text-sm text-ink-soft">
                            @if ($last)
                                {{ $last->sender_role->value === 'consultant' ? 'Consultant' : 'You' }}: {{ \Illuminate\Support\Str::limit($last->body, 90) }}
                            @else
                                No messages yet
                            @endif
                        </p>
                        <p class="mt-1 text-xs text-ink-muted">
                            @if ($thread->move) {{ $thread->move->reference }} · @endif
                            {{ $thread->messages_count }} message{{ $thread->messages_count === 1 ? '' : 's' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="rounded-full px-3 py-0.5 text-xs font-semibold {{ $thread->status->value === 'open' ? 'bg-brand/10 text-brand' : 'bg-paper-3 text-ink-muted' }}">
                            {{ $thread->status->label() }}
                        </span>
                    </div>
                </a>
            @empty
                <p class="rounded-xl border border-dashed border-line bg-paper-2 p-6 text-sm text-ink-soft">
                    No conversations yet — your consultant team opens one as your move kicks off.
                </p>
            @endforelse
        </section>

        {{ $threads->links() }}
    </div>
@endsection
