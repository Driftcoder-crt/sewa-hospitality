@extends('layouts.portal')

@section('title', 'Invitation expired — Sewa Hospitality')

@section('content')
    <div class="mx-auto max-w-md rounded-xl border border-line bg-paper-2 p-8 text-center">
        <h1 class="font-display text-2xl">This invitation link has expired.</h1>
        <p class="mt-3 text-sm text-ink-soft">
            Invitation links are valid for 72 hours and can be used once. Please ask your mobility
            manager (or write to {{ config('sewa.emails.support') }}) to send a fresh invite.
        </p>
        <a href="{{ route('login') }}" class="mt-6 inline-flex min-h-[44px] items-center rounded-full border border-brand px-6 text-sm font-semibold text-brand hover:bg-brand hover:text-brand-ink">
            Go to sign-in
        </a>
    </div>
@endsection
