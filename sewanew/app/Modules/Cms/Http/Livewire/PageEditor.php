<?php

namespace App\Modules\Cms\Http\Livewire;

use Livewire\Component;
use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Services\PageService;

class PageEditor extends Component
{
    public ?Page $page = null;
    public string $title = '';
    public string $slug = '';
    public string $content = '';
    public string $excerpt = '';
    public string $metaTitle = '';
    public string $metaDescription = '';
    public string $metaKeywords = '';
    public string $template = 'default';
    public ?int $parentId = null;
    public bool $isHome = false;
    public int $sortOrder = 0;
    public string $status = 'draft';
    public ?\DateTime $publishedAt = null;

    protected $rules = [
        'title' => 'required|string|max:255',
        'slug' => 'required|string|max:255|unique:cms_pages,slug',
        'content' => 'required|string',
        'excerpt' => 'nullable|string|max:500',
        'metaTitle' => 'nullable|string|max:255',
        'metaDescription' => 'nullable|string|max:160',
        'metaKeywords' => 'nullable|string|max:255',
        'template' => 'required|string|in:default,landing,full-width,minimal',
        'parent_id' => 'nullable|exists:cms_pages,id',
        'is_home' => 'boolean',
        'sort_order' => 'integer|min:0',
        'status' => 'required|in:draft,published,scheduled,archived',
        'published_at' => 'nullable|date',
    ];

    public function mount(?Page $page = null): void
    {
        if ($page) {
            $this->page = $page;
            $this->title = $page->title;
            $this->slug = $page->slug;
            $this->content = $page->content;
            $this->excerpt = $page->excerpt ?? '';
            $this->metaTitle = $page->meta_title ?? '';
            $this->metaDescription = $page->meta_description ?? '';
            $this->metaKeywords = $page->meta_keywords ?? '';
            $this->template = $page->template ?? 'default';
            $this->parentId = $page->parent_id;
            $this->isHome = $page->is_home;
            $this->sortOrder = $page->sort_order;
            $this->status = $page->status;
            $this->publishedAt = $page->published_at;
            
            $this->rules['slug'][1] = "unique:cms_pages,slug,{$page->id}";
        }
    }

    public function updatedSlug($value): void
    {
        $this->slug = \Str::slug($value);
    }

    public function save(): void
    {
        $this->validate();

        if (auth()->user()->cannot($this->page ? 'update' : 'create', $this->page ?? Page::class)) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        $data = [
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt ?: null,
            'meta_title' => $this->metaTitle ?: null,
            'meta_description' => $this->metaDescription ?: null,
            'meta_keywords' => $this->metaKeywords ?: null,
            'template' => $this->template,
            'parent_id' => $this->parentId,
            'is_home' => $this->isHome,
            'sort_order' => $this->sortOrder,
            'status' => $this->status,
            'published_at' => $this->publishedAt,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ];

        if ($this->page) {
            $this->page->update($data);
            session()->flash('success', 'Page updated successfully.');
        } else {
            $this->page = Page::create($data);
            session()->flash('success', 'Page created successfully.');
        }

        redirect()->route('admin.cms.pages.edit', $this->page);
    }

    public function publish(): void
    {
        if (!$this->page || auth()->user()->cannot('publish', $this->page)) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        $this->page->publish();
        session()->flash('success', 'Page published successfully.');
    }

    public function unpublish(): void
    {
        if (!$this->page || auth()->user()->cannot('unpublish', $this->page)) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        $this->page->unpublish();
        session()->flash('success', 'Page unpublished successfully.');
    }

    public function render()
    {
        return view('cms::livewire.page-editor');
    }
}
