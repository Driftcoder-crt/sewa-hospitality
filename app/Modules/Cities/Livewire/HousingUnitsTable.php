<?php

namespace App\Modules\Cities\Livewire;

use App\Modules\Cities\Enums\HousingTier;
use App\Modules\Cities\Enums\HousingType;
use App\Modules\Cities\Enums\RateUnit;
use App\Modules\Cities\Models\City;
use App\Modules\Cities\Models\HousingUnit;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Housing inventory admin (04-modules/10-cities-content.md §4.2–4.3):
 * CRUD + Sewa Verified action (admin+) + re-verification queue (units
 * verified ≥ 6 months ago) + rate-staleness flags.
 */
#[Layout('layouts.admin')]
class HousingUnitsTable extends Component
{
    use WithPagination;

    public function create(): void
    {
        $this->authorize('create', HousingUnit::class);

        $unit = HousingUnit::query()->create([
            'city_id' => City::query()->orderBy('name')->value('id'),
            'name' => 'New unit',
            'type' => HousingType::ServicedApartment->value,
            'tier' => HousingTier::Professional->value,
            'bedrooms' => 1,
            'rate_unit' => RateUnit::Night->value,
            'status' => 'draft',
            'published' => false,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        ActivityLogger::log('admin', 'create', $unit, ['name' => $unit->name]);

        $this->redirectRoute('admin.housing.edit', ['unit' => $unit->getKey()]);
    }

    /** One-click re-verify (cities doc §4.3) — badge date resets. */
    public function reverify(string $unitId): void
    {
        $unit = HousingUnit::query()->findOrFail($unitId);
        $this->authorize('verify', $unit);

        $unit->verified_at = now();
        $unit->verified_by_user_id = null;
        $unit->save();

        ActivityLogger::log('admin', 'update', $unit, ['reverified' => true, 'name' => $unit->name]);
        $this->dispatch('notify', tone: 'success', message: '"'.$unit->name.'" re-verified (badge date reset).');
    }

    public function expireBadge(string $unitId): void
    {
        $unit = HousingUnit::query()->findOrFail($unitId);
        $this->authorize('verify', $unit);

        $unit->verified_at = null;
        $unit->save();

        ActivityLogger::log('admin', 'update', $unit, ['badge_expired' => true, 'name' => $unit->name]);
        $this->dispatch('notify', tone: 'success', message: 'Badge expired — the listing shows without Sewa Verified.');
    }

    public function togglePublished(string $unitId): void
    {
        $unit = HousingUnit::query()->findOrFail($unitId);
        $this->authorize('update', $unit);

        $unit->published = ! $unit->published;
        $unit->save();

        ActivityLogger::log('admin', 'update', $unit, ['published' => $unit->published]);
    }

    public function render(): View
    {
        $this->authorize('viewAny', HousingUnit::class);

        return view('cities.livewire.housing-units-table', [
            'units' => HousingUnit::query()->with('city:id,name,slug')
                ->orderByDesc('updated_at')
                ->paginate(15),
            'reverification' => HousingUnit::query()
                ->whereNotNull('verified_at')
                ->where('verified_at', '<=', now()->subMonths(6))
                ->with('city:id,name')
                ->orderBy('verified_at')
                ->limit(10)
                ->get(),
        ]);
    }
}
