<?php

namespace App\Modules\Cms\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Services\PageService;

class PageList extends Component
{
    use WithPagination;

    public string $search = '';
    public ?string $status = null;
    public ?int $parent = null;
    public string $sortBy = 'updated_at';
    public string $sortDirection = 'desc';
    public int $perPage = 15;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => null],
        'parent' => ['except' => null],
        'sortBy' => ['except' => 'updated_at'],
        'sortDirection' => ['except' => 'desc'],
        'page' => ['except' => 1],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function render()
    {
        $query = Page::query()
            ->with(['author', 'parent'])
            ->when($this->search, fn($q) => 
                $q->where('title', 'like', "%{$this->search}%")
                 ->orWhere('slug', 'like', "%{$this->search}%")
            )
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->parent, fn($q) => $q->where('parent_id', $this->parent))
            ->orderBy($this->sortBy, $this->sortDirection);

        $pages = $query->paginate($this->perPage);

        return view('cms::livewire.page-list', [
            'pages' => $pages,
        ]);
    }

    public function deletePage($pageId): void
    {
        $page = Page::findOrFail($pageId);
        
        if (auth()->user()->cannot('delete', $page)) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        $page->delete();
        
        session()->flash('success', 'Page deleted successfully.');
    }
}
