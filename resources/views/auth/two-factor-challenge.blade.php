@extends('layouts.admin')

@section('title', 'Two-factor verification — Sewa Admin')

@section('content')
    <div class="mx-auto flex w-full max-w-sm flex-col justify-center py-8">
        <div class="rounded-xl border border-line bg-paper-2 p-6">
            <h1 class="font-display text-2xl text-ink">Two-factor verification</h1>

            <p class="mt-2 text-sm text-ink-soft">
                Enter the one-time code from your authenticator app to finish signing in.
            </p>

            <form method="POST" action="{{ route('two-factor.login') }}" class="mt-5 flex flex-col gap-4">
                @csrf

                <div>
                    <label for="code" class="mb-1 block text-sm font-medium text-ink">Authentication code</label>
                    <input id="code" name="code" type="text" inputmode="numeric" required autofocus
                           autocomplete="one-time-code"
                           class="min-h-[44px] w-full rounded-lg border border-line bg-paper-3 px-3 tracking-widest text-ink">
                    @error('code')
                        <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="min-h-[44px] w-full rounded-lg bg-brand px-4 text-sm font-semibold text-brand-ink">
                    Verify
                </button>
            </form>
        </div>
    </div>
@endsection
