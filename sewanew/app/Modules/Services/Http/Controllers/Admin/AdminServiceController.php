<?php

namespace App\Modules\Services\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Services\Models\Service;
use App\Modules\Services\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AdminServiceController extends Controller
{
    /**
     * Display a listing of services.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Service::class);

        $services = Service::with(['category', 'creator'])
            ->orderBy('order')
            ->paginate(20);

        return view('services::admin.index', compact('services'));
    }

    /**
     * Show the form for creating a new service.
     */
    public function create(): View
    {
        Gate::authorize('create', Service::class);

        $categories = ServiceCategory::orderBy('name')->get();

        return view('services::admin.create', compact('categories'));
    }

    /**
     * Store a newly created service in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Service::class);

        $validated = $request->validate([
            'category_id' => 'required|exists:service_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:services,slug',
            'excerpt' => 'nullable|string|max:500',
            'description' => 'required|string',
            'features' => 'nullable|array',
            'pricing' => 'nullable|string|max:255',
            'duration' => 'nullable|string|max:100',
            'order' => 'nullable|integer|min:0',
            'is_featured' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        $service = Service::create($validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service created successfully.');
    }

    /**
     * Display the specified service.
     */
    public function show(Service $service): View
    {
        Gate::authorize('view', $service);

        $service->load(['category', 'media', 'creator']);

        return view('services::admin.show', compact('service'));
    }

    /**
     * Show the form for editing the specified service.
     */
    public function edit(Service $service): View
    {
        Gate::authorize('update', $service);

        $categories = ServiceCategory::orderBy('name')->get();

        return view('services::admin.edit', compact('service', 'categories'));
    }

    /**
     * Update the specified service in storage.
     */
    public function update(Request $request, Service $service): RedirectResponse
    {
        Gate::authorize('update', $service);

        $validated = $request->validate([
            'category_id' => 'required|exists:service_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:services,slug,' . $service->id,
            'excerpt' => 'nullable|string|max:500',
            'description' => 'required|string',
            'features' => 'nullable|array',
            'pricing' => 'nullable|string|max:255',
            'duration' => 'nullable|string|max:100',
            'order' => 'nullable|integer|min:0',
            'is_featured' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        $service->update($validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service updated successfully.');
    }

    /**
     * Remove the specified service from storage.
     */
    public function destroy(Service $service): RedirectResponse
    {
        Gate::authorize('delete', $service);

        $service->delete();

        return redirect()->route('admin.services.index')
            ->with('success', 'Service deleted successfully.');
    }

    /**
     * Toggle service published status.
     */
    public function togglePublish(Service $service): RedirectResponse
    {
        Gate::authorize('publish', $service);

        $service->update([
            'published_at' => $service->published_at ? null : now(),
        ]);

        return redirect()->back()
            ->with('success', 'Service status updated successfully.');
    }
}
