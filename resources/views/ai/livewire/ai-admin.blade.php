<div class="admin-screen">
@section('title', 'AI console — Sewa Admin')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">AI console</h1>
            <p class="eyebrow mt-1 text-ink-muted">AI · admin+ · one toggle, platform unaffected</p>
        </div>
        <button type="button" wire:click="toggleGlobalKill" wire:loading.attr="disabled"
                class="inline-flex min-h-[44px] items-center rounded-full px-5 text-sm font-semibold {{ $globalEnabled ? 'bg-paper-3 text-danger-500 border border-line' : 'bg-brand text-brand-ink' }}">
            {{ $globalEnabled ? 'Kill all AI' : 'Re-enable AI' }}
        </button>
    </div>

    {{-- Providers (08-ai-system/01 §6: status chips — breaker, last latency, usage) --}}
    <h2 class="mt-6 font-display text-lg text-ink">Providers</h2>
    <div class="mt-2 grid gap-3 md:grid-cols-2">
        @foreach ($providers as $provider)
            <article class="rounded-xl border border-line bg-paper-2 p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-ink">
                        {{ $provider['id'] }}
                        @if ($provider['primary']) <span class="ms-1 rounded-full bg-brand/10 px-2 py-0.5 text-[10px] font-bold uppercase text-brand">primary</span> @endif
                    </p>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex min-h-[28px] items-center rounded-full px-3 text-xs font-semibold {{ $provider['breaker_open'] ? 'bg-danger-500/10 text-danger-500' : 'bg-brand/10 text-brand' }}">
                            breaker {{ $provider['breaker_open'] ? 'OPEN' : 'closed' }}
                        </span>
                        @if ($provider['breaker_open'])
                            <button type="button" wire:click="clearBreaker('{{ $provider['id'] }}')" wire:loading.attr="disabled"
                                    class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink hover:bg-paper-3">
                                Reset
                            </button>
                        @endif
                    </div>
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-2 text-xs text-ink-muted">
                    <div><dt class="font-semibold text-ink-soft">Configured</dt><dd>{{ $provider['configured'] ? 'yes' : 'missing key' }}</dd></div>
                    <div><dt class="font-semibold text-ink-soft">Last OK latency</dt><dd>{{ $provider['last_latency'] !== null ? $provider['last_latency'].' ms' : '—' }}</dd></div>
                    <div class="col-span-2"><dt class="font-semibold text-ink-soft">Endpoint</dt><dd class="truncate">{{ $provider['base_url'] }}</dd></div>
                </dl>
            </article>
        @endforeach
    </div>

    {{-- Budget gauges per feature (§6) --}}
    <h2 class="mt-6 font-display text-lg text-ink">Features &amp; monthly budgets</h2>
    <div class="mt-2 overflow-x-auto rounded-xl border border-line bg-paper-2">
        <table class="w-full min-w-[760px] text-sm">
            <thead>
                <tr class="border-b border-line text-ink-muted">
                    <th class="px-4 py-3 text-start font-semibold">Feature</th>
                    <th class="px-4 py-3 text-start font-semibold">Enabled</th>
                    <th class="px-4 py-3 text-start font-semibold">Tokens (month)</th>
                    <th class="px-4 py-3 text-start font-semibold">Calls (month)</th>
                    <th class="px-4 py-3 text-start font-semibold">Gauge</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($budgets as $budget)
                    @php($ratio = max($budget['usage']['token_ratio'], $budget['usage']['call_ratio']))
                    <tr class="border-b border-line last:border-0">
                        <td class="px-4 py-3 font-semibold text-ink">{{ $budget['feature']->label() }} <span class="text-xs text-ink-muted">({{ $budget['feature']->value }})</span></td>
                        <td class="px-4 py-3">
                            <button type="button" wire:click="toggleFeatureKill('{{ $budget['feature']->value }}')" wire:loading.attr="disabled"
                                    class="inline-flex min-h-[36px] items-center rounded-full px-3 text-xs font-semibold {{ $budget['enabled'] && $globalEnabled ? 'bg-brand/10 text-brand' : 'bg-danger-500/10 text-danger-500' }}">
                                {{ $budget['enabled'] && $globalEnabled ? 'on' : 'off' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-ink-soft">
                            {{ number_format($budget['usage']['tokens']) }} / {{ $budget['usage']['token_budget'] > 0 ? number_format($budget['usage']['token_budget']) : '∞' }}
                        </td>
                        <td class="px-4 py-3 text-ink-soft">
                            {{ number_format($budget['usage']['calls']) }} / {{ $budget['usage']['call_budget'] > 0 ? number_format($budget['usage']['call_budget']) : '∞' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="h-2 w-40 overflow-hidden rounded-full bg-paper-3" role="img"
                                 aria-label="{{ (int) round($ratio * 100) }}% of monthly budget used">
                                <div class="h-full rounded-full {{ $ratio >= 1 ? 'bg-danger-500' : ($ratio >= 0.8 ? 'bg-warning' : 'bg-brand') }}"
                                     style="width: {{ (int) round(max($ratio, $budget['usage']['calls'] > 0 ? 0.02 : 0) * 100) }}%"></div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Test-call console (admin only; every call lands in the ledger) --}}
    <h2 class="mt-6 font-display text-lg text-ink">Test-call console</h2>
    <form wire:submit="runTest" class="mt-2 rounded-xl border border-line bg-paper-2 p-4">
        <div class="flex flex-wrap items-end gap-3">
            <label class="block text-sm">
                <span class="font-semibold text-ink-soft">Feature</span>
                <select wire:model="testFeature" class="mt-1 min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                    @foreach ($features as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block flex-1 text-sm">
                <span class="font-semibold text-ink-soft">Prompt</span>
                <input type="text" wire:model="testPrompt" placeholder="Short test prompt…"
                       class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                @error('testPrompt') <span class="text-xs text-danger-500">{{ $message }}</span> @enderror
            </label>
            <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink">
                {{ $testRunning ? 'Calling…' : 'Send test call' }}
            </button>
        </div>
        @if ($testResult !== null)
            <div class="mt-3 rounded-lg bg-paper p-3 text-sm">
                @if ($testResult['ok'])
                    <p class="text-xs text-ink-muted">{{ $testResult['status'] }} via {{ $testResult['provider'] }} · {{ $testResult['model'] }} · {{ $testResult['latency_ms'] }} ms · tokens {{ $testResult['tokens'] }}</p>
                    <p class="mt-2 whitespace-pre-wrap text-ink-soft">{{ $testResult['content'] }}</p>
                @else
                    <p class="text-ink-soft">{{ $testResult['note'] }}</p>
                @endif
            </div>
        @endif
    </form>

    {{-- Invocation ledger --}}
    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-display text-lg text-ink">Invocation ledger</h2>
        <div class="flex flex-wrap items-end gap-3">
            <select wire:model="logFeature" aria-label="Filter by feature" class="min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                <option value="">All features</option>
                @foreach ($features as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <select wire:model="logStatus" aria-label="Filter by status" class="min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                <option value="">All statuses</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mt-2 max-h-96 overflow-y-auto rounded-xl border border-line bg-paper-2">
        <table class="w-full min-w-[720px] text-sm">
            <thead>
                <tr class="border-b border-line text-ink-muted">
                    <th class="px-4 py-3 text-start font-semibold">When</th>
                    <th class="px-4 py-3 text-start font-semibold">Feature</th>
                    <th class="px-4 py-3 text-start font-semibold">Provider / model</th>
                    <th class="px-4 py-3 text-start font-semibold">Tokens</th>
                    <th class="px-4 py-3 text-start font-semibold">Latency</th>
                    <th class="px-4 py-3 text-start font-semibold">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($log as $invocation)
                    <tr class="border-b border-line last:border-0">
                        <td class="px-4 py-3 text-ink-soft">{{ $invocation->created_at?->format('d M H:i') }}</td>
                        <td class="px-4 py-3 text-ink">{{ $invocation->feature->value }}</td>
                        <td class="px-4 py-3 text-ink-soft">{{ $invocation->provider }} · {{ \Illuminate\Support\Str::limit($invocation->model, 28) }}</td>
                        <td class="px-4 py-3 text-ink-soft">{{ $invocation->totalTokens() }}</td>
                        <td class="px-4 py-3 text-ink-soft">{{ $invocation->latency_ms }} ms</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex min-h-[28px] items-center rounded-full px-3 text-xs font-semibold {{ $invocation->status === \App\Modules\Ai\Enums\AiInvocationStatus::Ok ? 'bg-brand/10 text-brand' : ($invocation->status === \App\Modules\Ai\Enums\AiInvocationStatus::Error ? 'bg-danger-500/10 text-danger-500' : 'bg-warning/10 text-warning') }}">
                                {{ $invocation->status->label() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-ink-muted">No invocations yet — the ledger records every gateway call with no payloads.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
