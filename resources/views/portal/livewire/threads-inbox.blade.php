<div class="admin-screen">
@section('title', 'Threads — Sewa Admin')

    <h1 class="font-display text-2xl text-ink">Threads</h1>
    <p class="eyebrow mt-1 text-ink-muted">Portal ops · consultant inbox</p>

    <div class="mt-4 flex flex-wrap items-center gap-2">
        <input type="search" wire:model.live.debounce.400ms="q" placeholder="Subject or reference…"
               class="min-h-[44px] w-64 rounded-full border border-line bg-paper-2 px-4 text-sm focus:border-brand focus:outline-none">
        <select wire:model.live="status" class="min-h-[44px] rounded-full border border-line bg-paper-2 px-3 text-sm focus:border-brand focus:outline-none">
            <option value="">All</option>
            <option value="open">Open</option>
            <option value="closed">Closed</option>
        </select>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-5">
        {{-- Thread list --}}
        <div class="lg:col-span-2" wire:poll.30s>
            <div class="flex flex-col gap-2">
                @forelse ($threads as $thread)
                    <button type="button" wire:click="select('{{ $thread->id }}')"
                            class="rounded-xl border p-4 text-left transition {{ $activeId === $thread->id ? 'border-brand bg-brand/5' : 'border-line bg-paper-2 hover:border-brand/40' }}">
                        <p class="text-sm font-medium">{{ $thread->subject ?? 'Conversation' }}</p>
                        <p class="mt-0.5 text-xs text-ink-muted">
                            {{ $thread->move?->reference ?? 'No move' }} · {{ $thread->organization?->name }}
                        </p>
                        <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $thread->status->value === 'open' ? 'bg-brand/10 text-brand' : 'bg-paper-3 text-ink-muted' }}">
                            {{ $thread->status->label() }}
                        </span>
                    </button>
                @empty
                    <p class="rounded-xl border border-dashed border-line bg-paper-2 p-5 text-sm text-ink-soft">No threads.</p>
                @endforelse
            </div>
            {{ $threads->links() }}
        </div>

        {{-- Thread detail --}}
        <div class="lg:col-span-3">
            @if ($active)
                <div class="flex flex-col gap-4 rounded-xl border border-line bg-paper-2 p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="font-display text-lg">{{ $active->subject ?? 'Conversation' }}</h2>
                            <p class="text-xs text-ink-muted">{{ $active->move?->reference }} · {{ $active->organization?->name }}</p>
                        </div>
                        <button type="button" wire:click="{{ $active->status->value === 'open' ? 'close' : 'reopen' }}"
                                class="min-h-[44px] rounded-full border border-line px-4 text-xs font-semibold text-ink-soft hover:bg-paper-3">
                            {{ $active->status->value === 'open' ? 'Close thread' : 'Reopen' }}
                        </button>
                    </div>

                    <div class="max-h-96 overflow-y-auto pr-1 [scrollbar-width:thin]" wire:key="thread-{{ $active->id }}">
                        @forelse ($messages as $message)
                            <article class="mb-3 max-w-[85%] {{ $message->sender_role->value === 'consultant' ? 'ml-auto' : '' }}">
                                <div class="rounded-2xl px-4 py-2.5 text-sm {{ $message->sender_role->value === 'client' ? 'bg-paper-3' : 'bg-brand text-brand-ink' }}">
                                    {{ $message->body }}
                                </div>
                                <p class="mt-1 text-[11px] text-ink-muted">
                                    {{ $message->sender?->name ?? ucfirst($message->sender_role->value) }} · {{ $message->created_at->format('d M, H:i') }}
                                    @if ($message->read_at) · read @endif
                                </p>
                            </article>
                        @empty
                            <p class="text-sm text-ink-soft">No messages.</p>
                        @endforelse
                    </div>

                    @if ($active->status->value === 'open')
                        <form wire:submit="send" class="flex flex-col gap-2">
                            <textarea wire:model="reply" rows="3" placeholder="Reply with full context…"
                                      class="w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm focus:border-brand focus:outline-none"></textarea>
                            @error('reply') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                            <button type="submit" class="inline-flex min-h-[44px] items-center self-start rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink hover:opacity-90">Send reply</button>
                        </form>
                    @endif

                    {{-- Internal notes --}}
                    <div class="rounded-lg border border-dashed border-line p-4">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-ink-muted">Internal notes (never client-visible)</h3>
                        <form wire:submit="addNote" class="mt-2 flex flex-col gap-2">
                            <textarea wire:model="note" rows="2" placeholder="Context for the team…"
                                      class="w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm focus:border-brand focus:outline-none"></textarea>
                            @error('note') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                            <button type="submit" class="inline-flex min-h-[44px] items-center self-start rounded-full border border-line px-5 text-sm font-semibold text-ink-soft hover:bg-paper-3">Add note</button>
                        </form>
                        @forelse ($notes as $log)
                            <p class="mt-2 border-t border-line pt-2 text-xs text-ink-muted">
                                <strong class="text-ink-soft">{{ $log->changes['note'] ?? '' }}</strong>
                                — {{ $log->user?->name ?? 'system' }}, {{ $log->created_at->diffForHumans() }}
                            </p>
                        @empty
                            <p class="mt-2 text-xs text-ink-muted">No notes yet.</p>
                        @endforelse
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-dashed border-line bg-paper-2 p-8 text-center text-sm text-ink-soft">
                    Select a thread to reply with context — chat beats email.
                </div>
            @endif
        </div>
    </div>
</div>
