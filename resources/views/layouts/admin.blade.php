<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sewa Admin')</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    {{-- Alpine x-cloak guard (nonce'd script-src; styles keep the recorded unsafe-inline decision) --}}
    <style nonce="{{ $cspNonce ?? '' }}">[x-cloak]{display:none !important}</style>
</head>
<body x-data="{ open: false }" class="admin-density flex min-h-screen flex-col bg-paper text-ink">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:inline-flex focus:min-h-[44px] focus:items-center focus:rounded-lg focus:bg-brand focus:px-4 focus:text-sm focus:font-semibold focus:text-brand-ink">
        Skip to content
    </a>

    @php
        /*
        | Sidebar map (04-modules/05-admin-panel.md §3). M1 lights the
        | Content group (Pages/Menus/Redirects); the rest stay disabled
        | until their milestones land — never an empty clickable screen.
        */
        $adminSections = [
            'Content' => [
                ['label' => 'Pages', 'route' => 'admin.pages', 'live' => true],
                ['label' => 'Menus', 'route' => 'admin.menus', 'live' => true],
                ['label' => 'Redirects', 'route' => 'admin.redirects', 'live' => true],
            ],
            'Editorial' => [
                ['label' => 'Posts', 'route' => 'admin.posts', 'live' => true],
            ],
            'Services' => [
                ['label' => 'Service tree', 'route' => 'admin.services', 'live' => true],
            ],
            'Cities & Housing' => [
                ['label' => 'Cities', 'route' => 'admin.cities', 'live' => true],
                ['label' => 'Housing units', 'route' => 'admin.housing', 'live' => true],
            ],
            'Leads' => [
                ['label' => 'Leads inbox', 'route' => 'admin.leads', 'live' => true],
                ['label' => 'Pipeline', 'route' => 'admin.pipeline', 'live' => true],
                ['label' => 'Newsletter', 'route' => 'admin.newsletter', 'live' => true],
            ],
            'Careers & HR' => [
                ['label' => 'Job postings', 'route' => 'admin.jobs', 'live' => true],
                ['label' => 'Applications', 'route' => 'admin.applications', 'live' => true],
                ['label' => 'Employees', 'route' => 'admin.employees', 'live' => true],
            ],
            'Portal ops' => [
                ['label' => 'Moves', 'route' => 'admin.moves', 'live' => true],
                ['label' => 'Threads', 'route' => 'admin.threads', 'live' => true],
                ['label' => 'Invitations', 'route' => 'admin.invitations', 'live' => true],
            ],
            'Billing' => [
                ['label' => 'Quotes', 'route' => 'admin.quotes', 'live' => true],
                ['label' => 'Invoices', 'route' => 'admin.invoices', 'live' => true],
                ['label' => 'Payments', 'route' => 'admin.payments', 'live' => true],
                ['label' => 'Organizations', 'route' => 'admin.organizations', 'live' => true],
                ['label' => 'Reports', 'route' => 'admin.finance', 'live' => true],
            ],
            'Testimonials' => [
                ['label' => 'Testimonials', 'route' => 'admin.testimonials', 'live' => true],
            ],
            'CSR' => [
                ['label' => 'Programmes', 'route' => 'admin.csr', 'live' => true],
            ],
            'I18n' => [
                ['label' => 'Languages & translations', 'route' => 'admin.i18n', 'live' => true],
            ],
            'AI' => [
                ['label' => 'AI console', 'route' => 'admin.ai', 'live' => true],
            ],
            'Ops' => [],
            'System' => [
                ['label' => 'Data subject tool', 'route' => 'admin.privacy.data-subject', 'live' => true],
            ],
        ];

        $envTone = match (app()->environment()) {
            'production' => 'text-danger',
            'staging' => 'text-warning',
            default => 'text-ink-muted',
        };
    @endphp

    <div class="flex flex-1">
        {{-- Desktop sidebar — hidden on mobile --}}
        <aside class="hidden w-64 shrink-0 flex-col border-r border-line bg-paper-2 md:flex">
            <div class="border-b border-line px-5 py-6">
                <a href="{{ route('admin.dashboard') }}" class="block">
                    <span class="block font-display text-sm font-semibold tracking-wide text-ink">SEWA HOSPITALITY</span>
                    <span class="eyebrow mt-1 block text-ink-muted">Admin</span>
                </a>
            </div>

            <nav aria-label="Admin sections" class="flex-1 overflow-y-auto p-3">
                <ul class="flex flex-col gap-1">
                    <li>
                        <a href="{{ route('admin.dashboard') }}"
                           @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif
                           class="flex min-h-[44px] items-center rounded-lg px-3 text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-paper-3 text-ink' : 'text-ink-soft' }}">
                            Dashboard
                        </a>
                    </li>
                    @foreach ($adminSections as $section => $entries)
                        <li class="mt-2">
                            <span class="eyebrow block px-3 pb-1 text-ink-muted">{{ $section }}</span>
                            @forelse ($entries as $entry)
                                <a href="{{ route($entry['route']) }}"
                                   @if (request()->routeIs($entry['route'].'.*') || request()->routeIs($entry['route'])) aria-current="page" @endif
                                   class="flex min-h-[44px] items-center rounded-lg px-3 ps-5 text-sm font-medium {{ request()->routeIs($entry['route'].'.*') || request()->routeIs($entry['route']) ? 'bg-paper-3 text-ink' : 'text-ink-soft' }}">
                                    {{ $entry['label'] }}
                                </a>
                            @empty
                                {{-- Modules land screen by screen (04-modules/05-admin-panel.md §3). --}}
                                <span aria-disabled="true" class="flex min-h-[36px] items-center justify-between rounded-lg px-3 ps-5 text-sm text-ink-muted">
                                    Coming with M2+
                                </span>
                            @endforelse
                        </li>
                    @endforeach
                </ul>
            </nav>
        </aside>

        {{-- Mobile drawer, toggled by the topbar hamburger via Alpine --}}
        <div id="admin-mobile-nav" x-cloak x-show="open" @keydown.escape.window="open = false" class="fixed inset-0 z-40 flex md:hidden">
            <aside class="w-64 shrink-0 overflow-y-auto border-r border-line bg-paper-2">
                <div class="flex items-center justify-between border-b border-line px-4 py-4">
                    <span class="block font-display text-sm font-semibold tracking-wide text-ink">SEWA HOSPITALITY</span>
                    <span class="eyebrow text-ink-muted">Admin</span>
                </div>

                <nav aria-label="Admin sections" class="p-3">
                    <ul class="flex flex-col gap-1">
                        <li>
                            <a href="{{ route('admin.dashboard') }}"
                               @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif
                               @click="open = false"
                               class="flex min-h-[44px] items-center rounded-lg px-3 text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-paper-3 text-ink' : 'text-ink-soft' }}">
                                Dashboard
                            </a>
                        </li>
                        @foreach ($adminSections as $section => $entries)
                            <li class="mt-2">
                                <span class="eyebrow block px-3 pb-1 text-ink-muted">{{ $section }}</span>
                                @if (count($entries) > 0)
                                    @foreach ($entries as $entry)
                                        <a href="{{ route($entry['route']) }}" @click="open = false"
                                           class="flex min-h-[44px] items-center rounded-lg px-3 ps-5 text-sm font-medium text-ink-soft">
                                            {{ $entry['label'] }}
                                        </a>
                                    @endforeach
                                @else
                                    <span aria-disabled="true" class="flex min-h-[36px] items-center rounded-lg px-3 ps-5 text-sm text-ink-muted">M2+</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </aside>

            <button type="button" class="flex-1 cursor-default" aria-label="Close navigation" @click="open = false"></button>
        </div>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex items-center gap-3 border-b border-line bg-paper-2 px-4 py-3">
                <button type="button"
                        class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg border border-line text-ink md:hidden"
                        aria-controls="admin-mobile-nav" :aria-expanded="open" aria-label="Open navigation"
                        @click="open = true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.5" class="h-5 w-5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                </button>

                <span class="inline-flex items-center rounded-full border border-line px-2.5 py-1 text-xs font-semibold uppercase {{ $envTone }}">
                    {{ app()->environment() }}
                </span>

                {{-- ⌘K palette trigger (admin-panel §2: keyboard-first shell) --}}
                <button type="button"
                        class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-line px-3 text-sm text-ink-muted hover:bg-paper-3"
                        x-data @click="$dispatch('open-palette')"
                        aria-label="Open command palette (⌘K)">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>
                    <span class="hidden sm:inline">Search…</span>
                    <kbd class="hidden rounded border border-line bg-paper px-1.5 py-0.5 text-[10px] font-semibold sm:inline">⌘K</kbd>
                </button>

                @if (auth()->check())
                    <div class="ms-auto flex items-center gap-3">
                        <div class="hidden text-end sm:block">
                            <span class="block text-sm font-medium text-ink">{{ auth()->user()->name }}</span>
                            <span class="block text-xs text-ink-muted">{{ auth()->user()->email }}</span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex min-h-[44px] items-center rounded-lg bg-brand px-4 text-sm font-semibold text-brand-ink">
                                Sign out
                            </button>
                        </form>
                    </div>
                @endif
            </header>

            <main id="main-content" class="flex-1 p-4 md:p-6">
                @if (session('status'))
                    <p class="mb-4 rounded-lg border border-line bg-paper-3 px-4 py-3 text-sm text-ink-soft" role="status">
                        {{ session('status') }}
                    </p>
                @endif

                {{-- Dual-mode content area: @yield serves legacy @extends pages;
                     $slot serves Livewire full-page components injected via #[Layout('layouts.admin')]. --}}
                @yield('content')
                {{ $slot ?? '' }}
            </main>

            <footer class="mt-auto border-t border-line px-4 py-4 text-xs text-ink-muted">
                <p>SEWA HOSPITALITY SERVICES PVT. LTD. · Custom Livewire admin — no third-party admin packages.</p>
            </footer>
        </div>
    </div>

    {{-- ⌘K palette (role-scoped) + toast island — mounted once per shell. --}}
    <livewire:admin.command-palette />
    <livewire:admin.toasts />

    @livewireScripts
</body>
</html>
