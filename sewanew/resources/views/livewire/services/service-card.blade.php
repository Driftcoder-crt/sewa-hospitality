<div class="service-card bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow duration-300 overflow-hidden group">
    <div class="relative h-48 overflow-hidden">
        @if($service->getFirstMediaUrl('featured_image'))
            <img 
                src="{{ $service->getFirstMediaUrl('featured_image') }}" 
                alt="{{ $service->name }}"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            >
        @else
            <div class="w-full h-full bg-gradient-to-br from-[#0E7C66] to-[#C9974C] flex items-center justify-center">
                <svg class="w-16 h-16 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>
        @endif
        
        @if($service->is_featured)
            <span class="absolute top-3 right-3 bg-[#C9974C] text-white px-3 py-1 rounded-full text-xs font-semibold">
                ★ Featured
            </span>
        @endif
    </div>

    <div class="p-6">
        <div class="mb-2">
            <span class="text-xs font-medium text-[#0E7C66] uppercase tracking-wide">
                {{ $service->category->name ?? 'Service' }}
            </span>
        </div>

        <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-[#0E7C66] transition-colors">
            {{ $service->name }}
        </h3>

        <p class="text-gray-600 text-sm mb-4 line-clamp-2">
            {{ $service->excerpt }}
        </p>

        @if($service->pricing)
            <div class="mb-4">
                <span class="text-lg font-bold text-[#C9974C]">{{ $service->pricing }}</span>
                @if($service->duration)
                    <span class="text-sm text-gray-500">/ {{ $service->duration }}</span>
                @endif
            </div>
        @endif

        <div class="flex items-center justify-between mt-4">
            <button 
                wire:click="viewDetails"
                class="flex-1 bg-[#0E7C66] text-white px-6 py-2 rounded-lg hover:bg-[#0d6b58] transition-colors font-medium"
            >
                View Details
            </button>
        </div>
    </div>
</div>
