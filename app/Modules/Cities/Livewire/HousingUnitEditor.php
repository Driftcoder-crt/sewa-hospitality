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

/**
 * Housing unit editor (04-modules/10-cities-content.md §4.2): city,
 * type, tier, bedrooms, amenities checklist, honest from-rate + unit,
 * verification action (admin+). Rates are display "from" values —
 * no fake precision.
 */
#[Layout('layouts.admin')]
class HousingUnitEditor extends Component
{
    public HousingUnit $unit;

    public string $name = '';

    public string $city_id = '';

    public string $type = 'serviced-apartment';

    public string $locality = '';

    public string $area = '';

    public int $bedrooms = 1;

    public int $area_sqft = 0;

    public string $tier = 'professional';

    public string $from_rate = '';

    public string $rate_unit = 'night';

    public string $managed_by = '';

    public string $notes = '';

    public bool $published = false;

    /** @var list<string> */
    public array $amenities = [];

    public string $autosaveState = 'clean';

    public string $autosaveError = '';

    public function mount(HousingUnit $unit): void
    {
        $this->authorize('update', $unit);

        $this->name = $unit->name;
        $this->city_id = (string) $unit->city_id;
        $this->type = $unit->type->value;
        $this->locality = (string) $unit->locality;
        $this->area = (string) $unit->area;
        $this->bedrooms = $unit->bedrooms;
        $this->area_sqft = (int) $unit->area_sqft;
        $this->tier = $unit->tier->value;
        $this->from_rate = (string) $unit->from_rate;
        $this->rate_unit = $unit->rate_unit->value;
        $this->managed_by = (string) $unit->managed_by;
        $this->notes = (string) $unit->notes;
        $this->published = $unit->published;
        $this->amenities = $unit->amenities ?? [];
    }

    public function updated($property): void
    {
        $this->autosaveState = 'dirty';
    }

    public function autosave(): void
    {
        if ($this->autosaveState === 'dirty') {
            $this->save();
        }
    }

    public function save(): void
    {
        $this->autosaveState = 'saving';

        try {
            $this->authorize('update', $this->unit);

            if (trim($this->name) === '' || $this->city_id === '') {
                $this->autosaveState = 'error';
                $this->autosaveError = 'Name and city are required.';

                return;
            }

            $this->unit->fill([
                'name' => trim($this->name),
                'city_id' => $this->city_id,
                'type' => HousingType::from($this->type),
                'locality' => trim($this->locality) ?: null,
                'area' => trim($this->area) ?: null,
                'bedrooms' => max(0, (int) $this->bedrooms),
                'area_sqft' => max(0, (int) $this->area_sqft),
                'tier' => HousingTier::from($this->tier),
                'from_rate' => $this->from_rate !== '' ? max(0, (int) $this->from_rate) : null,
                'rate_unit' => RateUnit::from($this->rate_unit),
                'managed_by' => trim($this->managed_by) ?: null,
                'notes' => trim($this->notes) ?: null,
                'amenities' => array_values(array_filter(array_map('trim', $this->amenities))),
                'published' => $this->published,
                'updated_by' => auth()->id(),
            ])->save();

            ActivityLogger::log('admin', 'update', $this->unit, ['name' => $this->unit->name]);
            $this->autosaveState = 'saved';
        } catch (\Throwable $e) {
            $this->autosaveState = 'error';
            $this->autosaveError = 'Saving failed — your changes are still in the editor. Retry in a moment.';
        }
    }

    public function verify(): void
    {
        $this->authorize('verify', $this->unit);

        $this->unit->verified_at = now();
        $this->unit->verified_by_user_id = null;
        $this->unit->save();

        ActivityLogger::log('admin', 'update', $this->unit, ['verified' => true]);
        $this->dispatch('notify', tone: 'success', message: 'Sewa Verified set (dated today).');
    }

    public function addAmenity(string $value): void
    {
        $value = trim($value);
        if ($value !== '' && ! in_array($value, $this->amenities, true)) {
            $this->amenities[] = $value;
            $this->autosaveState = 'dirty';
        }
    }

    public function removeAmenity(int $index): void
    {
        unset($this->amenities[$index]);
        $this->amenities = array_values($this->amenities);
        $this->autosaveState = 'dirty';
    }

    public function render(): View
    {
        $this->authorize('viewAny', HousingUnit::class);

        return view('cities.livewire.housing-unit-editor', [
            'cities' => City::query()->orderBy('name')->get(['id', 'name']),
            'types' => HousingType::options(),
            'tiers' => HousingTier::options(),
            'units' => RateUnit::options(),
            'canVerify' => auth()->user()->can('verify', $this->unit),
            'suggested' => ['Wi-Fi', 'Housekeeping', 'Gym', 'Pool', 'Parking', 'Power backup', 'Air conditioning', 'Fully equipped kitchen', 'Security', 'Pet friendly'],
        ]);
    }
}
