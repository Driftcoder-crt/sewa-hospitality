<div class="admin-screen">
@section('title', ($invoice?->number ?? 'New invoice').' — Sewa Admin')

    @if ($invoice === null)
        {{-- CREATE MODE — standalone issue --}}
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <a href="{{ route('admin.invoices') }}" wire:navigate class="text-sm text-brand hover:underline">← All invoices</a>
                <h1 class="mt-1 font-display text-2xl text-ink">New invoice</h1>
                <p class="eyebrow mt-1 text-ink-muted">Standalone issue — or convert an accepted quote from its row</p>
            </div>
        </div>
        <form wire:submit="save" class="mt-6 flex flex-col gap-4 rounded-xl border border-line bg-paper-2 p-5">
            <div class="grid gap-3 md:grid-cols-3">
                <div>
                    <label class="text-xs font-semibold text-ink-muted" for="organizationId">Organization</label>
                    <select id="organizationId" wire:model="organizationId" required class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        <option value="">Choose…</option>
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                        @endforeach
                    </select>
                    @error('organizationId') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-ink-muted" for="moveId">Move record</label>
                    <select id="moveId" wire:model="moveId" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        <option value="">None</option>
                        @foreach ($moves as $move)
                            <option value="{{ $move->id }}">{{ $move->reference }} — {{ $move->organization?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-ink-muted" for="dueDate">Due date</label>
                    <input id="dueDate" type="date" wire:model="dueDate" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                </div>
            </div>
            <div class="flex flex-col gap-3">
                @foreach ($lines as $index => $line)
                    <div class="grid gap-2 md:grid-cols-12" wire:key="line-{{ $index }}">
                        <input type="text" wire:model="lines.{{ $index }}.description" placeholder="Description" class="md:col-span-5 min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        <input type="number" min="1" wire:model="lines.{{ $index }}.qty" placeholder="Qty" class="md:col-span-2 min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        <input type="number" step="0.01" min="0" wire:model="lines.{{ $index }}.rate" placeholder="Rate ₹" class="md:col-span-2 min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        <select wire:model="lines.{{ $index }}.tax_class" class="md:col-span-2 min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                            @foreach ([0, 5, 18, 28] as $class)
                                <option value="{{ $class }}">{{ $class }}%</option>
                            @endforeach
                        </select>
                        <button type="button" wire:click="removeLine({{ $index }})" class="md:col-span-1 min-h-[44px] rounded-lg text-sm font-medium text-ink-muted hover:text-danger">✕</button>
                    </div>
                @endforeach
            </div>
            <div class="flex items-center justify-between">
                <button type="button" wire:click="addLine" class="min-h-[44px] rounded-full border border-line px-5 text-sm font-semibold text-ink-soft hover:bg-paper-3">+ Add line</button>
                <button type="submit" class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink hover:opacity-90">Create draft invoice</button>
            </div>
            @error('lines') <p class="text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        </form>
    @else
        {{-- DETAIL MODE --}}
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <a href="{{ route('admin.invoices') }}" wire:navigate class="text-sm text-brand hover:underline">← All invoices</a>
                <h1 class="mt-1 font-display text-2xl text-ink">{{ $invoice->number }}</h1>
                <p class="eyebrow mt-1 text-ink-muted">
                    {{ $invoice->organization?->name }} · {{ $invoice->status->label() }}
                    @if ($invoice->move) · {{ $invoice->move->reference }} @endif
                    @if ($invoice->quote) · from {{ $invoice->quote->number }} @endif
                </p>
            </div>
            <div class="flex gap-2">
                <button type="button" wire:click="downloadPdf" class="inline-flex min-h-[44px] items-center rounded-full border border-line px-4 text-sm font-semibold text-ink-soft hover:bg-paper-3">PDF snapshot</button>
                @if (in_array($invoice->status->value, ['draft', 'sent']))
                    <button type="button" wire:click="send" wire:confirm="Queue the invoice email with the PDF attached?"
                            class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">
                        {{ $invoice->status->value === 'draft' ? 'Send' : 'Resend' }}
                    </button>
                @endif
            </div>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-3">
            <section class="xl:col-span-2 flex flex-col gap-6">
                <div class="overflow-x-auto rounded-xl border border-line bg-paper-2">
                    <table class="w-full min-w-[520px] text-sm">
                        <thead>
                            <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-muted">
                                <th class="px-4 py-3 font-semibold">Description</th>
                                <th class="px-4 py-3 text-right font-semibold">Qty</th>
                                <th class="px-4 py-3 text-right font-semibold">Rate</th>
                                <th class="px-4 py-3 text-right font-semibold">GST</th>
                                <th class="px-4 py-3 text-right font-semibold">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->lines as $line)
                                <tr class="border-b border-line last:border-0">
                                    <td class="px-4 py-3">{{ $line['description'] }}</td>
                                    <td class="px-4 py-3 text-right text-ink-soft">{{ $line['qty'] }}</td>
                                    <td class="px-4 py-3 text-right text-ink-soft">{{ \App\Modules\Billing\Services\TaxCalculator::money((int) $line['rate']) }}</td>
                                    <td class="px-4 py-3 text-right text-ink-soft">{{ $line['tax_class'] }}%</td>
                                    <td class="px-4 py-3 text-right font-medium">{{ \App\Modules\Billing\Services\TaxCalculator::money((int) $line['amount']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-line text-ink-soft"><td colspan="4" class="px-4 py-2 text-right">Subtotal</td><td class="px-4 py-2 text-right">{{ \App\Modules\Billing\Services\TaxCalculator::money($invoice->subtotal) }}</td></tr>
                            @foreach ($invoice->tax_breakdown ?? [] as $class => $amount)
                                <tr class="text-ink-soft"><td colspan="4" class="px-4 py-2 text-right">GST {{ $class }}%</td><td class="px-4 py-2 text-right">{{ \App\Modules\Billing\Services\TaxCalculator::money((int) $amount) }}</td></tr>
                            @endforeach
                            <tr class="font-semibold"><td colspan="4" class="px-4 py-3 text-right">Total</td><td class="px-4 py-3 text-right">{{ $invoice->formattedTotal() }}</td></tr>
                        </tfoot>
                    </table>
                </div>

                <section class="rounded-xl border border-line bg-paper-2">
                    <div class="border-b border-line p-5 pb-3">
                        <h2 class="font-display text-lg">Payments ({{ $invoice->payments->count() }})</h2>
                        <p class="mt-1 text-xs text-ink-muted">Balance {{ $invoice->formattedDue() }} · reminders sent {{ $invoice->reminders_sent }}/3 @if($invoice->last_reminder_at) · last {{ $invoice->last_reminder_at->format('d M') }} @endif</p>
                    </div>
                    <ul class="px-5" role="list">
                        @forelse ($invoice->payments as $payment)
                            <li class="flex items-center justify-between border-b border-line py-3 last:border-0 text-sm">
                                <div>
                                    <p class="font-medium">{{ $payment->formattedAmount() }} · {{ $payment->method->label() }}</p>
                                    <p class="text-xs text-ink-muted">{{ $payment->paid_at->format('d M Y') }} @if($payment->reference) · ref {{ $payment->reference }} @endif</p>
                                </div>
                                <span class="text-xs text-ink-muted">{{ $payment->recorder?->name ?? 'system' }}</span>
                            </li>
                        @empty
                            <li class="py-3 text-sm text-ink-soft">No payments recorded.</li>
                        @endforelse
                    </ul>
                    @if ($invoice->status->isOpen() || $invoice->status->value === 'draft')
                        <form wire:submit="recordPayment" class="grid gap-3 border-t border-line p-5 md:grid-cols-4">
                            <div>
                                <label class="text-xs font-semibold text-ink-muted" for="paymentAmount">Amount (₹)</label>
                                <input id="paymentAmount" type="number" step="0.01" wire:model="paymentAmount" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                                @error('paymentAmount') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-ink-muted" for="paymentMethod">Method</label>
                                <select id="paymentMethod" wire:model="paymentMethod" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                                    @foreach ($methods as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-ink-muted" for="paymentDate">Paid at</label>
                                <input id="paymentDate" type="date" wire:model="paymentDate" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-ink-muted" for="paymentReference">Reference (UTR)</label>
                                <input id="paymentReference" type="text" wire:model="paymentReference" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                            </div>
                            <div class="md:col-span-4">
                                <button type="submit" class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink hover:opacity-90">Record payment</button>
                            </div>
                        </form>
                    @endif
                </section>
            </section>

            <aside class="flex flex-col gap-4">
                <div class="rounded-xl border border-line bg-paper-2 p-5">
                    <h2 class="font-display text-lg">Send to</h2>
                    @if (in_array($invoice->status->value, ['draft', 'sent']))
                        <input type="email" wire:model="sendTo" placeholder="billing contact email"
                               class="mt-2 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        @error('sendTo') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    @else
                        <p class="mt-2 text-sm text-ink-soft">{{ $sendTo }}</p>
                    @endif
                    <p class="mt-2 text-xs text-ink-muted">Status never flips to sent unless the email actually queued.</p>
                </div>

                @if (! in_array($invoice->status->value, ['paid', 'void']))
                    <form wire:submit="void" class="rounded-xl border border-danger/30 bg-danger/5 p-5">
                        <h2 class="font-display text-lg text-danger">Void invoice</h2>
                        <p class="mt-1 text-xs text-ink-muted">Requires a reason (audited). The number retires forever.</p>
                        <textarea wire:model="voidReason" rows="2" placeholder="Reason…"
                                  class="mt-2 w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm focus:border-brand focus:outline-none"></textarea>
                        @error('voidReason') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        <button type="submit" wire:confirm="Void this invoice permanently?" class="mt-2 inline-flex min-h-[44px] items-center rounded-full border border-danger px-5 text-sm font-semibold text-danger hover:bg-danger hover:text-white">Void</button>
                    </form>
                @endif

                @if ($invoice->void_reason)
                    <div class="rounded-xl border border-line bg-paper-3 p-4 text-sm text-ink-soft">Void reason: {{ $invoice->void_reason }}</div>
                @endif
            </aside>
        </div>
    @endif
</div>
