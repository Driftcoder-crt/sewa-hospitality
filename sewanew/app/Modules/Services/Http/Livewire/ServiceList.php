<?php

namespace App\Modules\Services\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Modules\Services\Models\Service;
use App\Modules\Services\Models\ServiceCategory;

class ServiceList extends Component
{
    use WithPagination;

    public ?string $category = null;
    public ?string $search = null;
    public bool $featuredOnly = false;

    protected $queryString = [
        'category' => ['except' => ''],
        'search' => ['except' => ''],
        'featuredOnly' => ['except' => false],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Service::published()
            ->with(['category', 'media']);

        if ($this->category) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $this->category));
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->featuredOnly) {
            $query->where('is_featured', true);
        }

        $services = $query->orderBy('order')->paginate(12);

        $categories = ServiceCategory::withCount('services')->get();

        return view('livewire.services.service-list', [
            'services' => $services,
            'categories' => $categories,
        ]);
    }

    public function filterByCategory(?string $slug): void
    {
        $this->category = $slug;
        $this->resetPage();
    }

    public function toggleFeatured(): void
    {
        $this->featuredOnly = !$this->featuredOnly;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->category = null;
        $this->search = null;
        $this->featuredOnly = false;
        $this->resetPage();
    }
}
