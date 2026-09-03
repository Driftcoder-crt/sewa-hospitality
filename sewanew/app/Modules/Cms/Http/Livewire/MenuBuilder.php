<?php

namespace App\Modules\Cms\Http\Livewire;

use Livewire\Component;
use App\Modules\Cms\Models\Menu;
use App\Modules\Cms\Models\MenuItem;

class MenuBuilder extends Component
{
    public ?Menu $menu = null;
    public string $name = '';
    public string $location = '';
    public array $menuItems = [];
    public array $selectedItems = [];
    public int $maxDepth = 5;

    protected $rules = [
        'name' => 'required|string|max:255',
        'location' => 'required|string|max:100|unique:cms_menus,location',
    ];

    public function mount(?Menu $menu = null): void
    {
        if ($menu) {
            $this->menu = $menu;
            $this->name = $menu->name;
            $this->location = $menu->location;
            $this->loadMenuItems();
            
            $this->rules['location'][1] = "unique:cms_menus,location,{$menu->id}";
        }
    }

    protected function loadMenuItems(): void
    {
        if (!$this->menu) {
            return;
        }

        $items = $this->menu->items()
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        $this->menuItems = $items->toArray();
    }

    public function saveMenu(): void
    {
        $this->validate();

        if (auth()->user()->cannot($this->menu ? 'update' : 'create', $this->menu ?? Menu::class)) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        $data = [
            'name' => $this->name,
            'location' => $this->location,
            'is_active' => true,
            'created_by' => auth()->id(),
        ];

        if ($this->menu) {
            $this->menu->update($data);
            session()->flash('success', 'Menu updated successfully.');
        } else {
            $this->menu = Menu::create($data);
            session()->flash('success', 'Menu created successfully.');
            $this->loadMenuItems();
        }
    }

    public function addMenuItem(array $itemData): void
    {
        if (!$this->menu || auth()->user()->cannot('update', $this->menu)) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => $itemData['title'],
            'url' => $itemData['url'] ?? null,
            'page_id' => $itemData['page_id'] ?? null,
            'parent_id' => $itemData['parent_id'] ?? null,
            'sort_order' => $itemData['sort_order'] ?? $this->menu->items()->count(),
            'target' => $itemData['target'] ?? '_self',
            'icon' => $itemData['icon'] ?? null,
            'is_active' => true,
        ]);

        $this->loadMenuItems();
        session()->flash('success', 'Menu item added successfully.');
    }

    public function updateMenuItems(array $items): void
    {
        if (!$this->menu || auth()->user()->cannot('update', $this->menu)) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        $this->reorderItems($items);
        session()->flash('success', 'Menu order updated successfully.');
    }

    protected function reorderItems(array $items, ?int $parentId = null, int $order = 0): void
    {
        foreach ($items as $index => $item) {
            MenuItem::where('id', $item['id'])->update([
                'parent_id' => $parentId,
                'sort_order' => $order + $index,
            ]);

            if (isset($item['children']) && is_array($item['children'])) {
                $this->reorderItems($item['children'], $item['id'], $order);
            }
        }
    }

    public function deleteMenuItem(int $itemId): void
    {
        $item = MenuItem::findOrFail($itemId);
        
        if (auth()->user()->cannot('delete', $item)) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        // Delete children first
        foreach ($item->children as $child) {
            $this->deleteMenuItem($child->id);
        }

        $item->delete();
        $this->loadMenuItems();
        
        session()->flash('success', 'Menu item deleted successfully.');
    }

    public function toggleItemStatus(int $itemId): void
    {
        $item = MenuItem::findOrFail($itemId);
        
        if (auth()->user()->cannot('update', $item)) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        $item->update(['is_active' => !$item->is_active]);
        session()->flash('success', 'Menu item status updated.');
    }

    public function render()
    {
        return view('cms::livewire.menu-builder', [
            'menuItems' => $this->menu ? $this->menu->items()->with('children')->whereNull('parent_id')->orderBy('sort_order')->get() : collect(),
        ]);
    }
}
