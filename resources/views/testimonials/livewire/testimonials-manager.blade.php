<div class="admin-screen">
@section('title', 'Testimonials — Sewa Admin')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">Testimonials</h1>
            <p class="eyebrow mt-1 text-ink-muted">Trust · Moderation, Google reviews, requests</p>
        </div>
        <button type="button" wire:click="syncGbp"
                class="inline-flex min-h-[44px] items-center rounded-full border border-line px-5 text-sm font-semibold text-ink-soft hover:bg-paper-3">
            Re-sync Google reviews
        </button>
    </div>

    {{-- Live stats — honest as-of dating, never self-declared (§5). --}}
    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-line bg-paper-2 p-4">
            <p class="eyebrow text-ink-muted">Moderation queue</p>
            <p class="mt-1 font-display text-2xl text-ink">{{ $queue['pending'] }}</p>
        </div>
        <div class="rounded-xl border border-line bg-paper-2 p-4">
            <p class="eyebrow text-ink-muted">Google rating</p>
            <p class="mt-1 font-display text-2xl text-ink">
                @if ($stats !== null)
                    {{ $stats['rating'] }} ★ <span class="text-sm font-normal text-ink-muted">({{ $stats['count'] }} · as of {{ $stats['as_of'] }})</span>
                @else
                    <span class="text-sm font-normal text-ink-muted">Not synced yet</span>
                @endif
            </p>
        </div>
        <div class="rounded-xl border border-line bg-paper-2 p-4">
            <p class="eyebrow text-ink-muted">Cached reviews</p>
            <p class="mt-1 font-display text-2xl text-ink">{{ $queue['google'] }}</p>
        </div>
        <div class="rounded-xl border border-line bg-paper-2 p-4">
            <p class="eyebrow text-ink-muted">Requests queued / done</p>
            <p class="mt-1 font-display text-2xl text-ink">{{ $queue['requests'] }} <span class="text-sm font-normal text-ink-muted">/ {{ $queue['done'] }}</span></p>
        </div>
    </div>

    <div class="mt-5 flex flex-wrap items-center gap-2" role="tablist" aria-label="Testimonial tabs">
        @foreach (['moderation' => 'Moderation', 'google' => 'Google cache', 'requests' => 'Review requests'] as $key => $label)
            <button type="button" role="tab" wire:click="$set('tab', '{{ $key }}')"
                    aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                    class="inline-flex min-h-[44px] items-center rounded-full border px-4 text-sm font-semibold {{ $tab === $key ? 'border-brand bg-brand text-brand-ink' : 'border-line text-ink-soft hover:bg-paper-3' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($tab === 'moderation')
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <input type="search" wire:model.live.debounce.300ms="q" placeholder="Search client names…"
                   class="min-h-[44px] w-full max-w-xs rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand sm:w-64"
                   aria-label="Search testimonials">
            <select wire:model.live="status" aria-label="Filter by status"
                    class="min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                <option value="">All statuses</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="mt-4 grid gap-3">
            @forelse ($testimonials as $testimonial)
                <article class="rounded-xl border border-line bg-paper-2 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-display text-sm font-semibold text-ink">{{ $testimonial->displayName() }}</span>
                                @if ($testimonial->isVerified())
                                    <span class="inline-flex items-center rounded-full bg-success-500/15 px-2 py-0.5 text-xs font-semibold text-ink" title="Verified via Google sync or completed move">verified</span>
                                @endif
                                @if ($testimonial->google_review_id)
                                    <span class="inline-flex items-center rounded-full bg-paper-3 px-2 py-0.5 text-xs text-ink-soft">Google</span>
                                @endif
                                <span class="inline-flex items-center rounded-full bg-paper-3 px-2 py-0.5 text-xs text-ink-soft">{{ $testimonial->status->label() }}</span>
                            </div>
                            <p class="mt-1 text-xs text-ink-muted">
                                {{ str_repeat('★', (int) $testimonial->rating) }}{{ str_repeat('☆', max(0, 5 - (int) $testimonial->rating)) }}
                                @if ($testimonial->service) · {{ $testimonial->service->name }} @endif
                                @if ($testimonial->city) · {{ $testimonial->city->name }} @endif
                                · via {{ $testimonial->source->label() }}
                            </p>
                            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-ink-soft">{{ $testimonial->body }}</p>
                            @if ($testimonial->source_url)
                                <a href="{{ $testimonial->source_url }}" target="_blank" rel="noopener" class="mt-1 inline-block text-xs font-semibold text-ink-soft underline">Source ↗</a>
                            @endif
                        </div>
                        <div class="flex flex-col items-stretch gap-2">
                            @if ($testimonial->status->value !== 'published')
                                <button wire:click="publish('{{ $testimonial->getKey() }}')" type="button"
                                        class="inline-flex min-h-[36px] items-center justify-center rounded-lg bg-brand px-3 text-xs font-semibold text-brand-ink hover:opacity-90">Publish</button>
                            @endif
                            @if ($testimonial->status->value === 'published')
                                <button wire:click="archive('{{ $testimonial->getKey() }}')" type="button"
                                        class="inline-flex min-h-[36px] items-center justify-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">Archive</button>
                            @endif
                            <button wire:click="toggleConsent('{{ $testimonial->getKey() }}')" type="button"
                                    class="inline-flex min-h-[36px] items-center justify-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3"
                                    title="Toggle named-display consent">
                                {{ $testimonial->consent_named ? 'Name shown' : 'First name' }}
                            </button>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-line bg-paper-2 px-4 py-10 text-center">
                    <p class="font-display text-lg text-ink">Nothing in the queue</p>
                    <p class="mt-1 text-sm text-ink-soft">Consent-gated testimonials arrive from moves, forms and the Google sync.</p>
                </div>
            @endforelse
        </div>
        <div class="mt-4">{{ $testimonials->links() }}</div>
    @elseif ($tab === 'google')
        <div class="mt-4 overflow-x-auto rounded-xl border border-line bg-paper-2">
            <table class="w-full min-w-[720px] text-start text-sm">
                <thead>
                    <tr class="border-b border-line text-ink-muted">
                        <th class="px-4 py-3 text-start font-semibold">Reviewer</th>
                        <th class="px-4 py-3 text-start font-semibold">Rating</th>
                        <th class="px-4 py-3 text-start font-semibold">Text</th>
                        <th class="px-4 py-3 text-start font-semibold">Review date</th>
                        <th class="px-4 py-3 text-start font-semibold">Mirrored</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($googleReviews as $review)
                        <tr class="border-b border-line/60">
                            <td class="px-4 py-3 font-medium text-ink">{{ $review->reviewer }}</td>
                            <td class="px-4 py-3">{{ str_repeat('★', (int) $review->rating) }}</td>
                            <td class="max-w-md px-4 py-3 text-xs leading-relaxed text-ink-soft">{{ Str::limit((string) $review->text, 180) }}</td>
                            <td class="px-4 py-3 text-xs text-ink-soft">{{ $review->review_at?->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                @if ($review->synced)
                                    <span class="inline-flex items-center rounded-full bg-success-500/15 px-2 py-1 text-xs font-semibold text-ink">mirrored</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-paper-3 px-2 py-1 text-xs text-ink-muted">pending</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center">
                                <p class="font-display text-lg text-ink">No Google reviews cached</p>
                                <p class="mt-1 text-sm text-ink-soft">The GBP connector keys land with the M6 marketing wiring — until then this stays honestly empty.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $googleReviews->links() }}</div>
    @else
        <div class="mt-4 overflow-x-auto rounded-xl border border-line bg-paper-2">
            <table class="w-full min-w-[720px] text-start text-sm">
                <thead>
                    <tr class="border-b border-line text-ink-muted">
                        <th class="px-4 py-3 text-start font-semibold">Move reference</th>
                        <th class="px-4 py-3 text-start font-semibold">Recipient</th>
                        <th class="px-4 py-3 text-start font-semibold">Status</th>
                        <th class="px-4 py-3 text-start font-semibold">Sent</th>
                        <th class="px-4 py-3 text-start font-semibold">Follow-up</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $request)
                        <tr class="border-b border-line/60">
                            <td class="px-4 py-3 font-mono text-xs text-ink">{{ $request->move_reference }}</td>
                            <td class="px-4 py-3 text-ink-soft">{{ $request->recipient_name ?? $request->recipient_email }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $tones = ['queued' => 'bg-paper-3 text-ink-soft', 'sent' => 'bg-warning-500/15 text-ink', 'followed_up' => 'bg-accent/20 text-ink', 'done' => 'bg-success-500/15 text-ink', 'failed' => 'bg-danger-500/15 text-ink'];
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $tones[$request->status] ?? $tones['queued'] }}">{{ ucfirst($request->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-ink-soft">{{ $request->sent_at?->diffForHumans() ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-ink-soft">{{ $request->follow_up_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center">
                                <p class="font-display text-lg text-ink">No review requests yet</p>
                                <p class="mt-1 text-sm text-ink-soft">One request per move — created by the portal move-stage engine (M5 wiring).</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $requests->links() }}</div>
    @endif
</div>
