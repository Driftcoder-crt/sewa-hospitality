@extends('layouts.portal')

@section('title', 'Profile — Sewa Hospitality Portal')

@section('content')
    <div class="mx-auto flex max-w-2xl flex-col gap-6">
        <div class="flex flex-col gap-1">
            <p class="eyebrow text-ink-muted">Client portal</p>
            <h1 class="font-display text-3xl">Profile</h1>
            <p class="text-sm text-ink-soft">Your details, sign-in and preferences.</p>
        </div>

        <form method="POST" action="{{ route('portal.profile.update') }}" class="flex flex-col gap-5 rounded-xl border border-line bg-paper-2 p-6">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                       class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                @error('name') <p class="mt-1 text-xs text-danger" role="alert">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium">Email <span class="text-ink-muted">(sign-in — contact support to change)</span></label>
                <input id="email" type="email" value="{{ $user->email }}" disabled
                       class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper-3 px-3 text-sm text-ink-muted">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium">Phone</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" placeholder="+91 …"
                       class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                @error('phone') <p class="mt-1 text-xs text-danger" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="locale" class="block text-sm font-medium">Language</label>
                    <select id="locale" name="locale"
                            class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        <option value="en" @selected($user->locale === 'en')>English</option>
                        <option value="hi" @selected($user->locale === 'hi')>हिन्दी (Hindi)</option>
                    </select>
                </div>
                <div>
                    <label for="timezone" class="block text-sm font-medium">Timezone</label>
                    <select id="timezone" name="timezone"
                            class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        @foreach (['Asia/Kolkata', 'Asia/Tokyo', 'Asia/Seoul', 'Asia/Istanbul', 'Asia/Dubai', 'Europe/London', 'America/New_York', 'America/Los_Angeles', 'Australia/Sydney'] as $tz)
                            <option value="{{ $tz }}" @selected($user->timezone === $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <fieldset class="rounded-lg border border-line p-4">
                <legend class="px-1 text-sm font-medium">Change password <span class="font-normal text-ink-muted">(leave blank to keep)</span></legend>
                <div class="mt-2 grid gap-4">
                    <div>
                        <label for="password" class="block text-sm font-medium">New password</label>
                        <input id="password" name="password" type="password" autocomplete="new-password"
                               class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        <p class="mt-1 text-xs text-ink-muted">Minimum 12 characters with upper, lower, number and symbol.</p>
                        @error('password') <p class="mt-1 text-xs text-danger" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium">Confirm new password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                               class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                    </div>
                </div>
            </fieldset>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink hover:opacity-90">
                    Save changes
                </button>
            </div>
        </form>
    </div>
@endsection
