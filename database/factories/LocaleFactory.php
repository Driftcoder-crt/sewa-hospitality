<?php

namespace Database\Factories;

use App\Modules\I18n\Models\Locale;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Locale factory (03-database-schema §10). PK is the locale code. */
class LocaleFactory extends Factory
{
    protected $model = Locale::class;

    public function definition(): array
    {
        return [
            // Deterministic unique 3-letter test codes (z01, z02…) — never
            // colliding with the six seeded launch locales.
            'code' => 'z'.$this->faker->unique()->numberBetween(10, 999),
            'name' => fake()->unique()->languageName(),
            'native_name' => fake()->unique()->languageName(),
            'direction' => Locale::DIRECTION_LTR,
            'fallback_for' => null,
            'auto_translate' => false,
            'enabled' => true,
        ];
    }

    public function rtl(): static
    {
        return $this->state(fn (): array => ['direction' => Locale::DIRECTION_RTL]);
    }

    public function autoTranslated(): static
    {
        return $this->state(fn (): array => ['auto_translate' => true]);
    }

    public function disabled(): static
    {
        return $this->state(fn (): array => ['enabled' => false]);
    }
}
