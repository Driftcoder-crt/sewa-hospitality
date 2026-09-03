{{-- Newsletter manager (03-leads-crm §4.5): subscribers, stats,
     double-opt-in resend, issue composer. --}}
<div>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl">Newsletter</h1>
            <p class="mt-1 text-sm text-ink-muted">Double opt-in only — confirmed subscribers receive issues; everything else waits.</p>
        </div>
        <button type="button" wire:click="openComposer"
                class="inline-flex min-h-[44px] items-center rounded-lg bg-brand px-4 text-sm font-semibold text-brand-ink">
            Compose issue
        </button>
    </div>

    {{-- Stats --}}
    <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
        <div class="rounded-xl border border-line bg-paper-2 p-4">
            <dt class="text-xs uppercase text-ink-muted">Confirmed</dt>
            <dd class="mt-1 font-display text-2xl">{{ $stats['confirmed'] }}</dd>
        </div>
        <div class="rounded-xl border border-line bg-paper-2 p-4">
            <dt class="text-xs uppercase text-ink-muted">Pending</dt>
            <dd class="mt-1 font-display text-2xl">{{ $stats['pending'] }}</dd>
        </div>
        <div class="rounded-xl border border-line bg-paper-2 p-4">
            <dt class="text-xs uppercase text-ink-muted">Confirm rate</dt>
            <dd class="mt-1 font-display text-2xl">{{ $stats['confirm_rate'] }}%</dd>
        </div>
        <div class="rounded-xl border border-line bg-paper-2 p-4">
            <dt class="text-xs uppercase text-ink-muted">Unsubscribed</dt>
            <dd class="mt-1 font-display text-2xl">{{ $stats['unsubscribed'] }}</dd>
        </div>
        <div class="rounded-xl border border-line bg-paper-2 p-4">
            <dt class="text-xs uppercase text-ink-muted">Bounced</dt>
            <dd class="mt-1 font-display text-2xl">{{ $stats['bounced'] }}</dd>
        </div>
    </dl>

    {{-- Issue composer --}}
    @if ($showComposer)
        <div class="mt-5 rounded-xl border border-line bg-paper-2 p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">New issue (markdown)</h2>
            <div class="mt-3 grid gap-3">
                <input type="text" wire:model.live.debounce.300ms="issueSubject" placeholder="Subject line — honest, no clickbait"
                       class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                @error('issueSubject') <p class="text-xs text-danger-500">{{ $message }}</p> @enderror
                <textarea wire:model.live.debounce.300ms="issueBody" rows="8" placeholder="# Issue title&#10;&#10;Write the body in markdown…"
                          class="w-full rounded-lg border border-line bg-paper px-3 py-2.5 font-mono text-sm"></textarea>
                @error('issueBody') <p class="text-xs text-danger-500">{{ $message }}</p> @enderror
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="sendIssue" wire:loading.attr="disabled" wire:target="sendIssue"
                            wire:confirm="Queue this issue to every CONFIRMED subscriber?"
                            class="inline-flex min-h-[44px] items-center rounded-lg bg-brand px-4 text-sm font-semibold text-brand-ink">
                        Queue to confirmed subscribers
                    </button>
                    <span class="text-xs text-ink-muted">Queued per-subscriber with idempotency keys — retries never double-send. Pauses automatically if the email provider is degraded.</span>
                </div>
            </div>
        </div>
    @endif

    {{-- Subscribers table --}}
    <div class="mt-5 flex items-center gap-2">
        <select wire:model.live="statusFilter" class="min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm" aria-label="Filter by status">
            <option value="">All statuses</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-3 overflow-hidden rounded-xl border border-line bg-paper-2">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-muted">
                    <th class="px-3 py-3">Email</th>
                    <th class="px-3 py-3">Status</th>
                    <th class="hidden px-3 py-3 md:table-cell">Locale</th>
                    <th class="hidden px-3 py-3 md:table-cell">Since</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subscribers as $subscriber)
                    <tr class="border-b border-line last:border-0">
                        <td class="px-3 py-3">{{ $subscriber->email }}</td>
                        <td class="px-3 py-3"><span class="inline-flex rounded-full border border-line px-2.5 py-1 text-xs">{{ $subscriber->status->label() }}</span></td>
                        <td class="hidden px-3 py-3 text-xs text-ink-muted md:table-cell">{{ $subscriber->locale }}</td>
                        <td class="hidden px-3 py-3 text-xs text-ink-muted md:table-cell">{{ $subscriber->created_at->format('d M Y') }}</td>
                        <td class="px-3 py-3 text-end">
                            @if ($subscriber->status->value === 'pending')
                                <button type="button" wire:click="resendConfirm('{{ $subscriber->id }}')" class="text-xs font-medium text-brand hover:underline">Resend confirm</button>
                            @endif
                            @unless ($subscriber->status->value === 'unsubscribed')
                                · <button type="button" wire:click="unsubscribe('{{ $subscriber->id }}')" class="text-xs font-medium text-ink-muted hover:underline">Unsubscribe</button>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-10 text-center text-sm text-ink-muted">No subscribers yet — the E4 capture block and footer form feed this table.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $subscribers->links() }}</div>
</div>
