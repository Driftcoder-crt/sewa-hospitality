<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\I18n\Models\Locale;
use App\Modules\I18n\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** UI-string translation factory (03-database-schema §10). */
class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    public function definition(): array
    {
        return [
            'locale' => Locale::DEFAULT,
            'namespace' => 'site',
            'key' => fake()->unique()->slug(2).'.'.fake()->unique()->slug(2),
            'value' => fake()->sentence(6),
            // Reject-restore symmetry: a fresh machine row points its
            // preserved draft at the same value.
            'machine_value' => fn (array $attrs) => $attrs['value'] ?? null,
            'status' => 'machine',
            'reviewed_by' => null,
            'updated_at' => now(),
        ];
    }

    public function forLocale(string $code): static
    {
        return $this->state(fn (): array => ['locale' => $code]);
    }

    public function inNamespace(string $namespace): static
    {
        return $this->state(fn (): array => ['namespace' => $namespace]);
    }

    public function reviewed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'human-reviewed',
            'reviewed_by' => User::factory(),
        ]);
    }
}
