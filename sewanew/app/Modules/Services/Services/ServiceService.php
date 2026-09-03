<?php

namespace App\Modules\Services\Services;

use App\Modules\Services\Models\Service;
use App\Modules\Services\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ServiceService
{
    /**
     * Get all published services with optional filters.
     */
    public function getPublishedServices(
        ?string $categorySlug = null,
        ?string $search = null,
        bool $featuredOnly = false,
        int $perPage = 12
    ): LengthAwarePaginator {
        $query = Service::published()
            ->with(['category', 'media']);

        if ($categorySlug) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($featuredOnly) {
            $query->where('is_featured', true);
        }

        return $query->orderBy('order')->paginate($perPage);
    }

    /**
     * Get a single service by slug.
     */
    public function getBySlug(string $slug): Service
    {
        return Service::published()
            ->where('slug', $slug)
            ->with(['category', 'media', 'relatedServices'])
            ->firstOrFail();
    }

    /**
     * Get featured services.
     */
    public function getFeatured(int $limit = 6): Collection
    {
        return Service::published()
            ->where('is_featured', true)
            ->with(['category', 'media'])
            ->orderBy('order')
            ->limit($limit)
            ->get();
    }

    /**
     * Get related services.
     */
    public function getRelated(Service $service, int $limit = 4): Collection
    {
        return Service::published()
            ->where('id', '!=', $service->id)
            ->where('category_id', $service->category_id)
            ->with(['category', 'media'])
            ->orderBy('is_featured', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all service categories with counts.
     */
    public function getCategoriesWithCounts(): Collection
    {
        return ServiceCategory::withCount('services')->orderBy('name')->get();
    }

    /**
     * Search services.
     */
    public function search(string $query, int $limit = 10): Collection
    {
        return Service::published()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('features', 'like', "%{$query}%");
            })
            ->with(['category', 'media'])
            ->limit($limit)
            ->get();
    }
}
