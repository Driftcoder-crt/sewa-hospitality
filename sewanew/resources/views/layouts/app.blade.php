<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'SEWA Hospitality') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:400,500,600,700|inter:300,400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    
    <style>
        :root {
            --sewa-teal: #0E7C66;
            --sewa-teal-dark: #0A5C4B;
            --sewa-bronze: #C9974C;
            --sewa-bronze-light: #E5B86E;
            --sewa-sand: #F5F0EB;
            --sewa-charcoal: #2D3748;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            color: var(--sewa-charcoal);
            background-color: var(--sewa-sand);
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Fraunces', serif;
        }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex-shrink-0 flex items-center">
                        <span class="text-2xl font-bold text-[var(--sewa-teal)] font-fraunces">SEWA</span>
                    </a>
                    <div class="hidden sm:ml-8 sm:flex sm:space-x-8">
                        <a href="/" class="border-transparent text-gray-500 hover:border-[var(--sewa-teal)] hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Home</a>
                        <a href="/about" class="border-transparent text-gray-500 hover:border-[var(--sewa-teal)] hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">About</a>
                        <a href="/services" class="border-transparent text-gray-500 hover:border-[var(--sewa-teal)] hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Services</a>
                        <a href="/contact" class="border-transparent text-gray-500 hover:border-[var(--sewa-teal)] hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Contact</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                        <a href="/dashboard" class="text-sm font-medium text-gray-700 hover:text-[var(--sewa-teal)]">Dashboard</a>
                    @else
                        <a href="/login" class="text-sm font-medium text-gray-700 hover:text-[var(--sewa-teal)]">Log in</a>
                        <a href="/register" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-[var(--sewa-teal)] hover:bg-[var(--sewa-teal-dark)]">Register</a>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="flex-grow">
        @if(session('success'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                <div class="bg-green-50 border-l-4 border-green-400 p-4">
                    <p class="text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        @endif
        
        @if(session('error'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                <div class="bg-red-50 border-l-4 border-red-400 p-4">
                    <p class="text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        @endif
        
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-[var(--sewa-charcoal)] text-white mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-bold font-fraunces mb-4">SEWA Hospitality</h3>
                    <p class="text-gray-400 text-sm">Complete corporate relocation, mobility & hospitality platform.</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider mb-4">Services</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="#" class="hover:text-white">Corporate Housing</a></li>
                        <li><a href="#" class="hover:text-white">Relocation Services</a></li>
                        <li><a href="#" class="hover:text-white">Travel Management</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider mb-4">Company</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="#" class="hover:text-white">About Us</a></li>
                        <li><a href="#" class="hover:text-white">Careers</a></li>
                        <li><a href="#" class="hover:text-white">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider mb-4">Legal</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 pt-8 border-t border-gray-700 text-center text-gray-400 text-sm">
                &copy; {{ date('Y') }} SEWA Hospitality. All rights reserved.
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
