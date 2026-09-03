{{-- Newsletter confirm / unsubscribe result — honest utility page. --}}
@extends('layouts.app')

@section('title', $mode === 'confirm' ? 'Newsletter — Sewa Hospitality' : 'Unsubscribed — Sewa Hospitality')
@section('meta_description', 'Newsletter subscription status.')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <section data-theme="light" class="px-4 py-16 md:px-6 md:py-24">
        <div class="container mx-auto max-w-xl">
            @if (! $found)
                <h1 class="font-display text-3xl md:text-4xl">That link has expired</h1>
                <p class="mt-4 text-ink-soft">
                    We couldn't find a subscription for this link. If you'd like updates, simply
                    enter your email again in the form on our site — we'll send a fresh confirmation.
                </p>
            @elseif ($mode === 'confirm')
                @if ($fresh && $status === 'Confirmed')
                    <h1 class="font-display text-3xl md:text-4xl">You're subscribed. Welcome aboard.</h1>
                    <p class="mt-4 text-ink-soft">
                        Relocation guides, city notes and housing updates — a few times a month,
                        never more. Every email carries a one-click unsubscribe.
                    </p>
                @elseif ($status === 'Unsubscribed')
                    <h1 class="font-display text-3xl md:text-4xl">This link is no longer active</h1>
                    <p class="mt-4 text-ink-soft">
                        This address was unsubscribed earlier, so the confirm link is retired.
                        If you'd like to hear from us again, re-enter your email on our site.
                    </p>
                @else
                    <h1 class="font-display text-3xl md:text-4xl">Nothing to confirm</h1>
                    <p class="mt-4 text-ink-soft">This subscription is already in its current state — you're all set.</p>
                @endif
            @else
                <h1 class="font-display text-3xl md:text-4xl">You're unsubscribed. Done.</h1>
                <p class="mt-4 text-ink-soft">
                    No more emails from this list — the change is immediate. If it was a mistake,
                    re-enter your email on our site and we'll send a fresh confirmation link.
                </p>
            @endif

            <a href="{{ route('home') }}" class="mt-8 inline-flex min-h-[44px] items-center rounded-full border border-line px-6 text-sm font-semibold text-ink hover:bg-paper-3">
                Back to sewahospitality.com
            </a>
        </div>
    </section>
@endsection
