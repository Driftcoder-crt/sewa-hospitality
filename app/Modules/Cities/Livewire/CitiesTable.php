<?php

namespace App\Modules\Cities\Livewire;

use App\Modules\Cities\Enums\CityStatus;
use App\Modules\Cities\Models\City;
use App\Modules\Search\Models\SearchQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Cities table (04-modules/10-cities-content.md §4.1) + content
 * backlog pointer: zero-result search queries surface here as
 * editorial tickets (08-search §3).
 */
#[Layout('layouts.admin')]
class CitiesTable extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    public function create(): void
    {
        $this->authorize('create', City::class);

        $city = City::query()->create([
            'name' => 'New city',
            'slug' => 'new-city-'.mb_strtolower(Str::random(4)),
            'state' => '',
            'status' => CityStatus::Draft->value,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->redirectRoute('admin.cities.edit', ['city' => $city->getKey()]);
    }

    public function render(): View
    {
        $this->authorize('viewAny', City::class);

        return view('cities.livewire.cities-table', [
            'cities' => City::query()
                ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
                ->orderByDesc('is_hub')
                ->orderBy('name')
                ->paginate(15),
            'statuses' => CityStatus::options(),
            'backlog' => SearchQuery::query()
                ->where('zero_results', true)
                ->orderByDesc('count')
                ->limit(5)
                ->get(),
        ]);
    }
}
