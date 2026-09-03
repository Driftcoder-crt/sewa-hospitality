<?php

namespace App\Modules\Services\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Services\Models\Service;
use App\Modules\Services\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    /**
     * Display a listing of services.
     */
    public function index(Request $request): View
    {
        $category = $request->get('category');
        
        $services = Service::published()
            ->when($category, fn ($query) => $query->whereHas('category', fn ($q) => $q->where('slug', $category)))
            ->with(['category', 'media'])
            ->orderBy('order')
            ->paginate(12);

        $categories = ServiceCategory::withCount('services')->get();

        return view('services::index', compact('services', 'categories'));
    }

    /**
     * Display the specified service.
     */
    public function show(string $slug): View
    {
        $service = Service::published()
            ->where('slug', $slug)
            ->with(['category', 'media', 'relatedServices'])
            ->firstOrFail();

        return view('services::show', compact('service'));
    }
}
