<div class="admin-screen">
@section('title', 'Dashboard — Sewa Admin')

    <h1 class="font-display text-2xl text-ink">Dashboard</h1>
    <p class="eyebrow mt-1 text-ink-muted">Admin overview</p>

    <div class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-3">
        @foreach ([
            'Leads today' => $leadsToday,
            'SLA breaches' => $leadsSla,
            'Open moves' => $openMoves,
            'Applications' => $applications,
            'Reviews' => $reviews,
        ] as $label => $value)
            {{-- Values are wired in M3/M4/M5 by the owning modules — the
                 component passes null until then, so the tile renders an
                 honest em dash instead of an invented number. --}}
            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <p class="eyebrow text-ink-muted">{{ $label }}</p>
                <p class="mt-2 font-display text-2xl text-ink">{{ $value ?? '—' }}</p>
            </div>
        @endforeach

        <div class="rounded-xl border border-line bg-paper-2 p-4">
            <p class="eyebrow text-ink-muted">Queue</p>
            @if ($queue !== null)
                <p class="mt-2 font-display text-2xl text-ink">{{ $queue['failed'] }} failed</p>
                <p class="mt-1 text-sm text-ink-soft">{{ $queue['pending'] }} pending</p>
            @else
                <p class="mt-2 font-display text-2xl text-ink">—</p>
                <p class="mt-1 text-sm text-ink-muted">Queue tables not ready yet.</p>
            @endif
        </div>
    </div>

    <section class="mt-6 rounded-xl border border-line bg-paper-2 p-4" aria-labelledby="recent-activity-heading">
        <h2 id="recent-activity-heading" class="font-display text-lg text-ink">Recent activity</h2>

        @if ($activityFeed->isEmpty())
            <p class="mt-3 text-sm text-ink-muted">No activity yet — admin actions are logged here.</p>
        @else
            <ul class="mt-3 divide-y divide-line">
                @foreach ($activityFeed as $entry)
                    <li class="flex flex-wrap items-center gap-2 py-2.5 text-sm">
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $entry['context'] === 'admin' ? 'bg-brand text-brand-ink' : 'border border-line bg-paper-3 text-ink-soft' }}">
                            {{ $entry['context'] }}
                        </span>
                        <span class="text-ink">{{ $entry['action'] }}</span>
                        <span class="text-ink-muted">· {{ $entry['actor'] }}</span>
                        <span class="ml-auto text-ink-muted">{{ $entry['at'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
