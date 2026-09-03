@php($identity = \App\Support\Cms\Identity::current())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- The portal is never indexed (SetRequestContext also stamps area=app) --}}
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Portal — Sewa Hospitality')</title>
    <meta name="theme-color" content="#0E7C66">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col bg-paper text-ink">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:inline-flex focus:min-h-[44px] focus:items-center focus:rounded-lg focus:bg-brand focus:px-4 focus:text-sm focus:font-semibold focus:text-brand-ink">Skip to content</a>

    <header class="sticky top-0 z-40 border-b border-line bg-paper-2/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 md:px-6">
            <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-2">
                <span class="font-display text-sm font-semibold tracking-wide text-ink">SEWA HOSPITALITY</span>
                <span class="eyebrow hidden text-ink-muted sm:block">Client Portal</span>
            </a>

            <nav aria-label="Portal" class="hidden items-center gap-1 md:flex">
                @foreach ([
                    ['route' => 'portal.dashboard', 'label' => 'Dashboard'],
                    ['route' => 'portal.moves', 'label' => 'Moves'],
                    ['route' => 'portal.messages', 'label' => 'Messages'],
                    ['route' => 'portal.invoices', 'label' => 'Invoices'],
                ] as $item)
                    <a href="{{ route($item['route']) }}"
                       @if (request()->routeIs($item['route'])) aria-current="page" @endif
                       class="flex min-h-[44px] items-center rounded-full px-4 text-sm font-medium {{ request()->routeIs($item['route']) ? 'bg-brand text-brand-ink' : 'text-ink-soft hover:bg-paper-3 hover:text-ink' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                <a href="{{ route('portal.notifications') }}" aria-label="Notifications"
                   class="relative flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full text-ink-soft hover:bg-paper-3 hover:text-ink"
                   x-data x-init="$wire.__mount ? null : null">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                    <span id="portal-unread-badge" class="absolute -right-0.5 -top-0.5 hidden min-w-[18px] rounded-full bg-danger px-1 text-center text-[10px] font-bold leading-[18px] text-white">{{ $unreadCount ?? 0 }}</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex min-h-[44px] items-center rounded-full border border-line px-4 text-sm font-medium text-ink-soft hover:bg-paper-3 hover:text-ink">
                        Sign out
                    </button>
                </form>
            </div>
        </div>
        {{-- Mobile nav --}}
        <nav aria-label="Portal mobile" class="flex overflow-x-auto border-t border-line md:hidden">
            @foreach ([
                ['route' => 'portal.dashboard', 'label' => 'Dashboard'],
                ['route' => 'portal.moves', 'label' => 'Moves'],
                ['route' => 'portal.messages', 'label' => 'Messages'],
                ['route' => 'portal.invoices', 'label' => 'Invoices'],
                ['route' => 'portal.profile', 'label' => 'Profile'],
            ] as $item)
                <a href="{{ route($item['route']) }}"
                   @if (request()->routeIs($item['route'])) aria-current="page" @endif
                   class="flex min-h-[44px] shrink-0 items-center px-4 text-sm font-medium {{ request()->routeIs($item['route']) ? 'border-b-2 border-brand text-ink' : 'text-ink-soft' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </header>

    <main id="main" class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 md:px-6 md:py-10">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-brand/30 bg-brand/10 px-4 py-3 text-sm text-ink" role="status">
                {{ session('status') }}
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="mt-auto border-t border-line bg-paper-2">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 pb-[calc(1.5rem+env(safe-area-inset-bottom))] text-sm text-ink-muted md:flex-row md:items-center md:justify-between md:px-6">
            <p>© {{ now()->format('Y') }} {{ $identity['brand'] ?? 'Sewa Hospitality' }} — {{ $identity['slogan'] ?? 'Care, delivered.' }}</p>
            <p class="flex items-center gap-4">
                <a href="/legal/privacy-policy" class="hover:text-ink">Privacy</a>
                <a href="{{ route('portal.profile') }}" class="hover:text-ink">Profile</a>
            </p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
