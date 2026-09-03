@extends('layouts.portal')

@section('title', 'Notifications — Sewa Hospitality Portal')

@section('content')
    <div class="mx-auto flex max-w-3xl flex-col gap-6">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="eyebrow text-ink-muted">Client portal</p>
                <h1 class="font-display text-3xl">Notifications</h1>
            </div>
            @if ($notifications->contains(fn ($n) => $n->read_at === null))
                <form method="POST" action="{{ route('portal.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="min-h-[44px] rounded-full border border-line px-4 text-sm font-medium text-ink-soft hover:bg-paper-3 hover:text-ink">
                        Mark all as read
                    </button>
                </form>
            @endif
        </div>

        <ul role="list" class="flex flex-col gap-3">
            @forelse ($notifications as $notification)
                <li class="rounded-xl border p-4 {{ $notification->read_at ? 'border-line bg-paper-2' : 'border-brand/30 bg-brand/5' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm {{ $notification->read_at ? 'text-ink-soft' : 'font-semibold' }}">{{ $notification->title }}</p>
                            @if ($notification->body)
                                <p class="mt-1 text-sm text-ink-soft">{{ $notification->body }}</p>
                            @endif
                            <p class="mt-1 text-xs text-ink-muted">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        @if ($notification->read_at === null)
                            <form method="POST" action="{{ route('portal.notifications.read', $notification) }}">
                                @csrf
                                <button type="submit" class="min-h-[44px] rounded-full border border-line px-3 text-xs font-medium text-ink-soft hover:bg-paper-3 hover:text-ink">
                                    {{ $notification->url ? 'Open' : 'Mark read' }}
                                </button>
                            </form>
                        @endif
                    </div>
                </li>
            @empty
                <li class="rounded-xl border border-dashed border-line bg-paper-2 p-6 text-sm text-ink-soft">
                    All quiet — stage changes, documents and replies land here.
                </li>
            @endforelse
        </ul>

        {{ $notifications->links() }}
    </div>
@endsection
