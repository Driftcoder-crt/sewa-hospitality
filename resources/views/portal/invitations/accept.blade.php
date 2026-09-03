@extends('layouts.portal')

@section('title', 'Set up your access — Sewa Hospitality')

@section('content')
    <div class="mx-auto flex max-w-md flex-col gap-6 rounded-xl border border-line bg-paper-2 p-6 md:p-8">
        <div>
            <p class="eyebrow text-ink-muted">Client portal invitation</p>
            <h1 class="mt-1 font-display text-2xl">Welcome, {{ $user->name }}</h1>
            <p class="mt-2 text-sm text-ink-soft">
                You have been invited as
                @forelse ($organizations as $organization)
                    {{ $loop->last && !$loop->first ? ' & ' : '' }}<strong>{{ $organization->name }}</strong>
                @empty
                    a portal member
                @endforelse .
                Set a password to activate your account.
            </p>
        </div>

        <form method="POST" action="{{ route('portal.invitations.store', $token) }}" class="flex flex-col gap-4">
            @csrf
            <div>
                <label for="password" class="block text-sm font-medium">Create password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password"
                       class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                <p class="mt-1 text-xs text-ink-muted">Minimum 12 characters with upper, lower, number and symbol.</p>
                @error('password') <p class="mt-1 text-xs text-danger" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                       class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
            </div>
            <button type="submit" class="inline-flex min-h-[44px] items-center justify-center rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink hover:opacity-90">
                Activate my account
            </button>
        </form>
    </div>
@endsection
