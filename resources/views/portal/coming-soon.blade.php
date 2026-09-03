@extends('layouts.app')

@section('title', 'Portal — Sewa Hospitality')

@section('content')
    {{-- M0 portal placeholder (routes/portal.php → portal.dashboard etc.).
         The real portal (moves, documents, threads, invoices) is Milestone 5. --}}
    <section class="flex min-h-[60vh] items-center justify-center px-4 md:px-6 py-16">
        <div class="w-full max-w-md rounded-xl border border-line bg-paper-2 p-6 md:p-8">
            <p class="eyebrow text-ink-muted">Client Portal</p>
            <h1 class="font-display mt-2 text-3xl">Your portal is being prepared.</h1>
            <p class="mt-3 text-ink-soft">Moves, documents, threads and invoices arrive with Milestone 5.</p>
            <p class="mt-4 text-sm text-ink-muted">Signed in as {{ auth()->user()?->email }}</p>
            <form method="POST" action="{{ route('logout') }}" class="mt-6">
                @csrf
                <button type="submit" class="min-h-[44px] inline-flex items-center rounded-full border border-line bg-paper-2 px-6 text-sm font-medium text-ink">
                    Sign out
                </button>
            </form>
        </div>
    </section>
@endsection
