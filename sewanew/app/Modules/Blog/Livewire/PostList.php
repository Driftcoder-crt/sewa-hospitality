<?php

namespace App\Modules\Blog\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Modules\Blog\Models\Post;
use App\Modules\Blog\Models\Category;

class PostList extends Component
{
    use WithPagination;

    public ?string $category = null;
    public string $search = '';
    public string $sortBy = 'latest';

    protected $queryString = [
        'category' => ['except' => ''],
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'latest'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Post::with(['author', 'category'])
            ->where('status', 'published')
            ->where('published_at', '<=', now());

        if ($this->category) {
            $query->where('category_id', $this->category);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('excerpt', 'like', "%{$this->search}%");
            });
        }

        switch ($this->sortBy) {
            case 'popular':
                $query->orderBy('views_count', 'desc');
                break;
            case 'oldest':
                $query->orderBy('published_at', 'asc');
                break;
            default:
                $query->orderBy('published_at', 'desc');
        }

        $posts = $query->paginate(12);
        $categories = Category::where('is_active', true)->get();

        return view('blog::livewire.post-list', [
            'posts' => $posts,
            'categories' => $categories,
        ]);
    }

    public function filterByCategory(?string $categoryId): void
    {
        $this->category = $categoryId;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->category = null;
        $this->search = '';
        $this->sortBy = 'latest';
        $this->resetPage();
    }
}
