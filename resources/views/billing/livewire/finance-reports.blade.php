<div class="admin-screen">
@section('title', 'Finance reports — Sewa Admin')

    <div wire:poll.60s>
        <h1 class="font-display text-2xl text-ink">Finance reports</h1>
        <p class="eyebrow mt-1 text-ink-muted">Billing · revenue, aging, win rate — cached 5 min</p>

        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <p class="eyebrow text-ink-muted">Outstanding</p>
                <p class="mt-1 font-display text-2xl">{{ \App\Modules\Billing\Services\TaxCalculator::money($outstandingTotal) }}</p>
            </div>
            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <p class="eyebrow text-ink-muted">Quote win rate</p>
                <p class="mt-1 font-display text-2xl">{{ $winRate ?? '—' }}</p>
            </div>
            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <p class="eyebrow text-ink-muted">Max overdue bucket</p>
                <p class="mt-1 font-display text-2xl">
                    {{ \App\Modules\Billing\Services\TaxCalculator::money(max($aging[3]['amount'], 0)) }}
                </p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            {{-- Revenue bars (pure CSS — no chart lib) --}}
            <section class="rounded-xl border border-line bg-paper-2 p-5" aria-label="Monthly revenue">
                <h2 class="font-display text-lg">Revenue — last 6 months</h2>
                @php($max = max(1, max(array_merge(
                    array_column($months, 'invoiced'),
                    array_column($months, 'collected'),
                ))))
                <div class="mt-4 flex flex-col gap-3">
                    @foreach ($months as $month)
                        <div>
                            <div class="flex items-center justify-between text-xs text-ink-muted">
                                <span>{{ $month['label'] }}</span>
                                <span>Invoiced {{ \App\Modules\Billing\Services\TaxCalculator::money($month['invoiced']) }} · Collected {{ \App\Modules\Billing\Services\TaxCalculator::money($month['collected']) }}</span>
                            </div>
                            <div class="mt-1 h-3 overflow-hidden rounded-full bg-paper-3">
                                <div class="h-full bg-brand" style="width: {{ (int) round($month['invoiced'] / $max * 100) }}%"></div>
                            </div>
                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-paper-3">
                                <div class="h-full bg-accent" style="width: {{ (int) round($month['collected'] / $max * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-xl border border-line bg-paper-2 p-5" aria-label="Outstanding aging">
                <h2 class="font-display text-lg">Outstanding aging</h2>
                <div class="mt-4 flex flex-col gap-3">
                    @foreach ($aging as $bucket)
                        <div class="flex items-center justify-between rounded-lg border border-line px-4 py-3">
                            <span class="text-sm text-ink-soft">{{ $bucket['bucket'] }}</span>
                            <span class="font-semibold {{ $bucket['bucket'] === '30+ days' && $bucket['amount'] > 0 ? 'text-danger' : '' }}">
                                {{ \App\Modules\Billing\Services\TaxCalculator::money($bucket['amount']) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</div>
