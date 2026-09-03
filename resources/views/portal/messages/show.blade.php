@extends('layouts.portal')

@section('title', ($thread->subject ?? 'Conversation').' — Sewa Hospitality Portal')

@section('content')
    <div class="mx-auto flex max-w-3xl flex-col gap-6">
        <div>
            <a href="{{ route('portal.messages') }}" class="text-sm font-medium text-brand hover:underline">← All messages</a>
            <h1 class="mt-2 font-display text-2xl">{{ $thread->subject ?? 'Conversation' }}</h1>
            @if ($thread->move)
                <p class="mt-1 text-sm text-ink-soft">{{ $thread->move->reference }} · {{ $thread->move->stage->label() }}</p>
            @endif
        </div>

        {{-- Messages — server-rendered; the island polls for new rows
             (native transport, 11-realtime §3: chat poll 10s). --}}
        <div id="thread-messages"
             wire:poll.10s
             class="flex flex-col gap-4 rounded-xl border border-line bg-paper-2 p-5">
            @forelse ($messages as $message)
                <article class="max-w-[85%] {{ $message->sender_role->value === 'client' ? 'self-end' : '' }}">
                    <div class="rounded-2xl px-4 py-3 text-sm leading-relaxed
                        {{ $message->isSystem()
                            ? 'bg-paper-3 text-ink-muted'
                            : ($message->sender_role->value === 'client'
                                ? 'bg-brand text-brand-ink'
                                : 'bg-paper-3 text-ink') }}">
                        {{ $message->body }}
                    </div>
                    <p class="mt-1 text-xs text-ink-muted {{ $message->sender_role->value === 'client' ? 'text-right' : '' }}">
                        @if ($message->isSystem())
                            System
                        @elseif ($message->sender_role->value === 'consultant')
                            {{ $message->sender?->name ?? 'Consultant' }}
                        @else
                            You
                        @endif
                        · {{ $message->created_at->format('d M, H:i') }}
                    </p>
                </article>
            @empty
                <p class="text-sm text-ink-soft">No messages yet — say hello.</p>
            @endforelse
        </div>

        {{-- Composer: validation errors KEEP the body (04 doc §6 —
             never lost typing). --}}
        @if ($thread->status->value === 'open')
            <form method="POST" action="{{ route('portal.messages.store', $thread) }}" class="flex flex-col gap-3">
                @csrf
                <div>
                    <label for="body" class="sr-only">Your message</label>
                    <textarea id="body" name="body" rows="3" required maxlength="5000"
                              placeholder="Write to your consultant team…"
                              class="w-full rounded-xl border border-line bg-paper-2 px-4 py-3 text-sm placeholder:text-ink-muted focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20 {{ $errors->has('body') ? 'border-danger' : '' }}">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="mt-1 text-xs text-danger" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-center justify-between">
                    <p class="text-xs text-ink-muted">Replies within the published window — business hours, IST.</p>
                    <button type="submit" class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink hover:opacity-90">
                        Send
                    </button>
                </div>
            </form>
        @else
            <p class="rounded-xl border border-line bg-paper-3 p-4 text-sm text-ink-soft">
                This conversation is closed. Start a new topic with your consultant on the dashboard.
            </p>
        @endif
    </div>
@endsection
