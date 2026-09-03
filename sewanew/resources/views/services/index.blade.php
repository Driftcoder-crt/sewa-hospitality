@extends('layouts.app')

@section('title', 'Our Services')
@section('description', 'Explore our comprehensive range of hospitality services designed to exceed your expectations.')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-[#0E7C66] to-[#0a5f4d] text-white py-16">
        <div class="container-sewa mx-auto px-4">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Our Services</h1>
            <p class="text-xl opacity-90 max-w-3xl">
                Discover exceptional hospitality solutions tailored to your needs
            </p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-sewa mx-auto px-4 py-12">
        <livewire:services.service-list />
    </div>
</div>
@endsection
