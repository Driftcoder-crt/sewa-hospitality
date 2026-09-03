@extends('layouts.admin')

@section('title', 'Services Management')

@section('content')
<div class="py-8">
    <div class="container-sewa mx-auto px-4">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Services</h1>
                <p class="text-gray-600 mt-1">Manage your hospitality services</p>
            </div>
            <a href="{{ route('admin.services.create') }}" class="bg-[#0E7C66] text-white px-6 py-3 rounded-lg hover:bg-[#0d6b58] transition font-medium flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add New Service
            </a>
        </div>

        <!-- Success Message -->
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <!-- Services Table -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pricing</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($services as $service)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-12 w-12 flex-shrink-0">
                                        @if($service->getFirstMediaUrl('featured_image'))
                                            <img class="h-12 w-12 rounded-lg object-cover" src="{{ $service->getFirstMediaUrl('featured_image') }}" alt="">
                                        @else
                                            <div class="h-12 w-12 rounded-lg bg-gradient-to-br from-[#0E7C66] to-[#C9974C]"></div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $service->name }}</div>
                                        <div class="text-sm text-gray-500">{{ Str::limit($service->excerpt, 50) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                    {{ $service->category->name ?? 'Uncategorized' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $service->pricing ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($service->published_at)
                                    <span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Published</span>
                                @else
                                    <span class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">Draft</span>
                                @endif
                                @if($service->is_featured)
                                    <span class="ml-2 px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Featured</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $service->order ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.services.edit', $service) }}" class="text-[#0E7C66] hover:text-[#0d6b58]">Edit</a>
                                    <form action="{{ route('admin.services.toggle-publish', $service) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-blue-600 hover:text-blue-800">
                                            {{ $service->published_at ? 'Unpublish' : 'Publish' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.services.destroy', $service) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                No services found. Create your first service!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($services->hasPages())
            <div class="mt-6">
                {{ $services->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
