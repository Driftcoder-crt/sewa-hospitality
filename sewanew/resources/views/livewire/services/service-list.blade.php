<div class="service-list">
    <div class="mb-6 flex flex-wrap gap-4 items-center justify-between">
        <!-- Search -->
        <div class="relative w-full md:w-64">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search"
                placeholder="Search services..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0E7C66] focus:border-transparent"
            >
            @if($search)
                <button 
                    wire:click="$set('search', '')"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            @endif
        </div>

        <!-- Filters -->
        <div class="flex gap-2">
            <button 
                wire:click="toggleFeatured"
                class="px-4 py-2 rounded-lg {{ $featuredOnly ? 'bg-[#0E7C66] text-white' : 'bg-gray-100 text-gray-700' }} hover:opacity-90 transition"
            >
                {{ $featuredOnly ? '★ Featured Only' : '☆ Featured' }}
            </button>
            @if($category || $search || $featuredOnly)
                <button 
                    wire:click="clearFilters"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                >
                    Clear All
                </button>
            @endif
        </div>
    </div>

    <!-- Category Filter -->
    @if($categories->count() > 0)
        <div class="mb-6 flex flex-wrap gap-2">
            <button 
                wire:click="filterByCategory(null)"
                class="px-4 py-2 rounded-full text-sm {{ !$category ? 'bg-[#0E7C66] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }} transition"
            >
                All Services
            </button>
            @foreach($categories as $cat)
                <button 
                    wire:click="filterByCategory('{{ $cat->slug }}')"
                    class="px-4 py-2 rounded-full text-sm {{ $category === $cat->slug ? 'bg-[#C9974C] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }} transition"
                >
                    {{ $cat->name }} ({{ $cat->services_count }})
                </button>
            @endforeach
        </div>
    @endif

    <!-- Services Grid -->
    @if($services->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($services as $service)
                <livewire:services.service-card :service="$service" :key="'service-' . $service->id" />
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $services->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <h3 class="text-lg font-semibold text-gray-700">No services found</h3>
            <p class="text-gray-500 mt-2">Try adjusting your search or filters</p>
        </div>
    @endif
</div>
