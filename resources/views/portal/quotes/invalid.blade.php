@extends('layouts.portal')

@section('title', 'Quote link not valid — Sewa Hospitality')

@section('content')
    <div class="mx-auto max-w-md rounded-xl border border-line bg-paper-2 p-8 text-center">
        <h1 class="font-display text-2xl">This quote link is not valid.</h1>
        <p class="mt-3 text-sm text-ink-soft">
            The link may belong to a different email, or the quote was already answered.
            Check the latest quote email in your inbox, or write to
            <a href="mailto:{{ config('sewa.emails.billing') }}" class="text-brand hover:underline">{{ config('sewa.emails.billing') }}</a>.
        </p>
    </div>
@endsection
