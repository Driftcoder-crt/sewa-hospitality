@extends('layouts.app')

@section('title', $service->name)
@section('description', $service->excerpt)

@section('content')
<div class="bg-white min-h-screen">
    <!-- Hero Image -->
    @if($service->getFirstMediaUrl('featured_image'))
        <div class="h-64 md:h-96 relative overflow-hidden">
            <img 
                src="{{ $service->getFirstMediaUrl('featured_image') }}" 
                alt="{{ $service->name }}"
                class="w-full h-full object-cover"
            >
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-8 text-white">
                <div class="container-sewa mx-auto px-4">
                    <span class="text-sm font-medium text-[#C9974C] uppercase tracking-wide">
                        {{ $service->category->name ?? 'Service' }}
                    </span>
                    <h1 class="text-4xl md:text-5xl font-bold mt-2">{{ $service->name }}</h1>
                </div>
            </div>
        </div>
    @else
        <div class="h-64 md:h-96 bg-gradient-to-r from-[#0E7C66] to-[#C9974C] flex items-center justify-center">
            <div class="text-center text-white">
                <svg class="w-24 h-24 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <h1 class="text-4xl md:text-5xl font-bold">{{ $service->name }}</h1>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <div class="container-sewa mx-auto px-4 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Details -->
            <div class="lg:col-span-2">
                @if($service->pricing)
                    <div class="mb-8 p-6 bg-gray-50 rounded-xl">
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-bold text-[#0E7C66]">{{ $service->pricing }}</span>
                            @if($service->duration)
                                <span class="text-gray-600">per {{ $service->duration }}</span>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="prose prose-lg max-w-none">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">About This Service</h2>
                    <div class="text-gray-700 leading-relaxed">
                        {!! $service->description !!}
                    </div>
                </div>

                @if($service->features && count($service->features) > 0)
                    <div class="mt-8">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">What's Included</h3>
                        <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($service->features as $feature)
                                <li class="flex items-start gap-3">
                                    <svg class="w-6 h-6 text-[#0E7C66] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700">{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="sticky top-8 space-y-6">
                    <!-- Contact CTA -->
                    <div class="bg-[#0E7C66] text-white p-6 rounded-xl">
                        <h3 class="text-xl font-bold mb-4">Interested in this service?</h3>
                        <p class="mb-6 opacity-90">Get in touch with our team to learn more or request a custom quote.</p>
                        <a href="{{ route('contact') }}" class="block w-full bg-white text-[#0E7C66] text-center py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                            Contact Us
                        </a>
                    </div>

                    <!-- Service Details -->
                    <div class="bg-gray-50 p-6 rounded-xl">
                        <h4 class="font-bold text-gray-900 mb-4">Service Details</h4>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Category:</dt>
                                <dd class="font-medium text-gray-900">{{ $service->category->name ?? 'N/A' }}</dd>
                            </div>
                            @if($service->duration)
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Duration:</dt>
                                <dd class="font-medium text-gray-900">{{ $service->duration }}</dd>
                            </div>
                            @endif
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Published:</dt>
                                <dd class="font-medium text-gray-900">{{ $service->published_at->format('M d, Y') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Services -->
        @if($service->relatedServices && $service->relatedServices->count() > 0)
            <div class="mt-16">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Related Services</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($service->relatedServices as $related)
                        <livewire:services.service-card :service="$related" :key="'related-' . $related->id" />
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
