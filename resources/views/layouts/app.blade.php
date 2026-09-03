@php($identity = \App\Support\Cms\Identity::current())
@php($headerMenu = app(\App\Modules\Cms\Services\MenuService::class)->treeSafe(\App\Modules\Cms\Enums\MenuLocation::Header, app()->getLocale()))
@php($footerMenu = app(\App\Modules\Cms\Services\MenuService::class)->treeSafe(\App\Modules\Cms\Enums\MenuLocation::Footer, app()->getLocale()))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sewa Hospitality — Care, delivered.')</title>
    <meta name="description" content="@yield('meta_description', 'Corporate relocation, global mobility and hospitality services in India — housing, immigration and settling-in care.')">
    <meta name="theme-color" content="#0E7C66">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @stack('head')
    <x-site.analytics />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body id="top" class="min-h-screen flex flex-col bg-paper text-ink">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:inline-flex focus:min-h-[44px] focus:items-center focus:rounded-lg focus:bg-brand focus:px-4 focus:text-sm focus:font-semibold focus:text-brand-ink">Skip to content</a>

    <x-site.header :items="$headerMenu" />

    <x-site.locale-banner />

    <main id="main" class="flex-1">
        {{-- Dual-mode content area: @yield serves legacy @extends pages;
             $slot serves Livewire full-page components (search island)
             injected via #[Layout('layouts.app')]. --}}
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    <x-site.footer :items="$footerMenu" />

    <x-site.consent-banner />

    @livewireScripts
</body>
</html>
