@extends('layouts.admin')

@section('title', 'Sign in — Sewa Admin')

@section('content')
    <div class="mx-auto flex w-full max-w-sm flex-col justify-center py-8">
        <div class="rounded-xl border border-line bg-paper-2 p-6">
            <h1 class="font-display text-2xl text-ink">Sign in</h1>

            @if (session('status'))
                <p class="mt-4 rounded-lg bg-paper-3 px-3 py-2 text-sm text-ink-soft" role="status">
                    {{ session('status') }}
                </p>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-5 flex flex-col gap-4">
                @csrf
                <x-honeypot />

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-ink">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           autocomplete="email"
                           class="min-h-[44px] w-full rounded-lg border border-line bg-paper-3 px-3 text-ink">
                    @error('email')
                        <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-ink">Password</label>
                    <input id="password" name="password" type="password" required
                           autocomplete="current-password"
                           class="min-h-[44px] w-full rounded-lg border border-line bg-paper-3 px-3 text-ink">
                    @error('password')
                        <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                @if (config('sewa.turnstile.site_key'))
                    {{-- Cloudflare Turnstile (error lock #3); the widget script is
                         the only third-party JS permitted pre-consent. --}}
                    <div class="cf-turnstile" data-sitekey="{{ config('sewa.turnstile.site_key') }}" data-theme="light"></div>
                    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                @endif

                <button type="submit"
                        class="min-h-[44px] w-full rounded-lg bg-brand px-4 text-sm font-semibold text-brand-ink">
                    Sign in
                </button>
            </form>
        </div>

        <p class="eyebrow mt-6 text-center text-ink-muted">Care, delivered.</p>
    </div>
@endsection
