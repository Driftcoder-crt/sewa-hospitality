@extends('layouts.portal')

@section('title', 'Quote '.($quote->number ?? '').' — Sewa Hospitality')

@section('content')
    <div class="mx-auto flex max-w-2xl flex-col gap-6">
        @if (session('decision'))
            <div class="rounded-xl border p-6 text-center {{ session('decision') === 'accept' ? 'border-brand/40 bg-brand/5' : 'border-line bg-paper-2' }}" role="status">
                <p class="font-display text-2xl">
                    {{ session('decision') === 'accept' ? 'Thank you — the quote is accepted.' : 'The quote has been declined.' }}
                </p>
                <p class="mt-2 text-sm text-ink-soft">
                    @if (session('decision') === 'accept')
                        Our team has been notified and an invoice will follow. A copy of this confirmation
                        stays available at this link.
                    @else
                        No further action is needed. If this was a mistake, reply to the quote email and
                        your consultant will re-open it.
                    @endif
                </p>
            </div>
        @endif

        <section class="rounded-xl border border-line bg-paper-2 p-6">
            <p class="eyebrow text-ink-muted">{{ $quote->organization?->name }}</p>
            <h1 class="mt-1 font-display text-2xl">Quote {{ $quote->number }}
                <span class="ml-2 align-middle text-sm font-normal text-ink-soft">v{{ $quote->version }}</span>
            </h1>
            <p class="mt-1 text-sm text-ink-soft">
                @if ($quote->move) Move {{ $quote->move->reference }} · @endif
                Valid until {{ $quote->valid_until?->format('d M Y') ?? '—' }}
            </p>

            <table class="mt-6 w-full text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-muted">
                        <th class="py-2 font-semibold">Description</th>
                        <th class="py-2 text-right font-semibold">Qty</th>
                        <th class="py-2 text-right font-semibold">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quote->lines as $line)
                        <tr class="border-b border-line">
                            <td class="py-2.5">{{ $line['description'] }}</td>
                            <td class="py-2.5 text-right text-ink-soft">{{ $line['qty'] }}</td>
                            <td class="py-2.5 text-right font-medium">{{ \App\Modules\Billing\Services\TaxCalculator::money((int) $line['amount']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="text-base font-semibold">
                        <td class="py-3" colspan="2">Total <span class="text-xs font-normal text-ink-muted">(incl. GST)</span></td>
                        <td class="py-3 text-right">{{ $quote->formattedTotal() }}</td>
                    </tr>
                </tbody>
            </table>

            @if ($quote->notes)
                <p class="mt-4 rounded-lg bg-paper-3 p-3 text-sm text-ink-soft">{{ $quote->notes }}</p>
            @endif

            @if ($quote->isAcceptable() && ! session('decision'))
                <form method="POST" action="{{ route('portal.quotes.decide', ['quote' => $quote->getKey(), 'token' => $token]) }}"
                      class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end"
                      x-data
                      @submit="$el.querySelectorAll('button').forEach(b => b.disabled = true)">
                    @csrf
                    <button type="submit" name="decision" value="reject"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-full border border-line px-6 text-sm font-medium text-ink-soft hover:bg-paper-3">
                        Decline
                    </button>
                    <button type="submit" name="decision" value="accept"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink hover:opacity-90">
                        Accept quote
                    </button>
                </form>
                <p class="mt-2 text-xs text-ink-muted">Accepting notifies our team to schedule the work and raise the invoice. One decision per quote.</p>
            @elseif ($quote->status->value === 'accepted')
                <p class="mt-6 text-sm font-medium text-brand">This quote was accepted{{ $quote->accepted_at?->format(' d M Y') }}.</p>
            @elseif ($quote->status->value === 'rejected')
                <p class="mt-6 text-sm text-ink-soft">This quote was declined.</p>
            @elseif ($quote->isExpired())
                <p class="mt-6 text-sm text-ink-soft">This quote has expired. Contact your consultant for a refreshed version.</p>
            @endif
        </section>
    </div>
@endsection
