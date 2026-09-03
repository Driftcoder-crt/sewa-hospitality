@extends('layouts.app')

@section('title', 'Status — Sewa Hospitality')

@section('content')
    @php
        /*
        | Honest /status page (06-hosting-deployment.md §9, 12-monitoring.md §5).
        | Dot colors come from semantic tokens only; 'unknown' is rendered as
        | its own neutral state, never folded into green.
        */
        $dots = [
            'ok' => 'text-success',
            'green' => 'text-success',
            'amber' => 'text-warning',
            'red' => 'text-danger',
            'unknown' => 'text-ink-muted',
        ];

        $dotClass = fn (string $status): string => $dots[$status] ?? 'text-ink-muted';
    @endphp

    <p class="eyebrow text-ink-muted">SYSTEM STATUS</p>
    <h1 class="font-display text-2xl text-ink">Status</h1>

    <div class="mt-5 grid gap-3 md:grid-cols-2">
        @foreach (['website' => 'Website', 'api' => 'API', 'portal' => 'Portal'] as $key => $label)
            <div class="flex items-center justify-between rounded-xl border border-line bg-paper-2 p-4">
                <div class="flex items-center gap-3">
                    <span class="{{ $dotClass($checks[$key]['status']) }}" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10"
                             fill="currentColor" stroke="currentColor" stroke-width="1">
                            <circle cx="5" cy="5" r="4"/>
                        </svg>
                    </span>
                    <span class="text-sm font-medium text-ink">{{ $label }}</span>
                </div>
                <span class="text-sm text-ink-soft">{{ ucfirst($checks[$key]['status']) }}</span>
            </div>
        @endforeach

        <div class="rounded-xl border border-line bg-paper-2 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="{{ $dotClass($checks['scheduler']['status']) }}" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10"
                             fill="currentColor" stroke="currentColor" stroke-width="1">
                            <circle cx="5" cy="5" r="4"/>
                        </svg>
                    </span>
                    <span class="text-sm font-medium text-ink">Scheduler</span>
                </div>
                <span class="text-sm text-ink-soft">{{ ucfirst($checks['scheduler']['status']) }}</span>
            </div>
            <p class="mt-2 text-sm text-ink-muted">
                @if ($checks['scheduler']['last_tick_age'] === null)
                    No tick yet — cron has not reported a heartbeat.
                @else
                    Last tick {{ $checks['scheduler']['last_tick_age'] }}s ago
                @endif
            </p>
        </div>

        <div class="rounded-xl border border-line bg-paper-2 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="{{ $dotClass($checks['queue']['status']) }}" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10"
                             fill="currentColor" stroke="currentColor" stroke-width="1">
                            <circle cx="5" cy="5" r="4"/>
                        </svg>
                    </span>
                    <span class="text-sm font-medium text-ink">Queue</span>
                </div>
                <span class="text-sm text-ink-soft">{{ ucfirst($checks['queue']['status']) }}</span>
            </div>
            <p class="mt-2 text-sm text-ink-muted">
                @if ($checks['queue']['pending'] === null || $checks['queue']['failed'] === null)
                    Queue tables not ready — no counts yet.
                @else
                    {{ $checks['queue']['pending'] }} pending · {{ $checks['queue']['failed'] }} failed
                @endif
            </p>
        </div>
    </div>

    <p class="mt-5 text-sm text-ink-muted">
        Checked at {{ $computed_at }} — updated every 30 seconds.
    </p>
@endsection
