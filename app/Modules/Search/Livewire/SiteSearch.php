<?php

namespace App\Modules\Search\Livewire;

use App\Modules\Search\Services\SearchService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * /search island (08-search §3): grouped tabs, debounced 250ms, min 2
 * chars, zero-state suggests top services (never a dead end). The page
 * itself is noindex, follow (§6).
 */
#[Layout('layouts.app')]
class SiteSearch extends Component
{
    public string $q = '';

    public string $activeTab = 'services';

    public function updatedQ(): void
    {
        $this->activeTab = 'services';
    }

    public function render(SearchService $search): View
    {
        $results = $this->q !== ''
            ? $search->search($this->q, app()->getLocale())
            : ['total' => 0, 'term' => '', 'groups' => []];

        $tabs = collect($results['groups'])
            ->filter(fn (array $group): bool => $group['count'] > 0);

        if (! $tabs->has($this->activeTab)) {
            $this->activeTab = $tabs->keys()->first() ?? 'services';
        }

        return view('search.livewire.site-search', [
            'results' => $results,
            'tabs' => $tabs,
        ]);
    }
}
