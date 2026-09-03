<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Portal\Enums\DocumentCategory;
use App\Modules\Portal\Enums\DocumentVisibility;
use App\Modules\Portal\Models\PortalDocument;
use App\Modules\Portal\Models\PortalMove;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Portal document factory (schema §8). Media fixtures carry alt text
 * by the shared MediaFactory contract; the disk stays whatever the
 * media row declares (documents are private — tests attach a real
 * file when they exercise the download flow).
 */
class PortalDocumentFactory extends Factory
{
    protected $model = PortalDocument::class;

    public function definition(): array
    {
        return [
            'move_record_id' => PortalMove::factory(),
            'organization_id' => function (array $attrs): string {
                $move = PortalMove::query()->find($attrs['move_record_id']);

                return $move?->organization_id ?? Organization::factory()->create()->getKey();
            },
            'user_id' => User::factory(),
            'uploaded_by' => User::factory(),
            // Titles must be UNIQUE per row — the visibility-matrix tests
            // distinguish documents by title, and one shared default made
            // assertDontSee unsatisfiable for same-move rows.
            'title' => 'Employment visa — stamped copy #'.fake()->unique()->numberBetween(1000, 9999),
            'media_id' => Media::factory(),
            'category' => DocumentCategory::Visa,
            'visible_to' => DocumentVisibility::Both,
            'expires_at' => fake()->optional(0.7)->dateTimeBetween('+1 month', '+2 years'),
        ];
    }

    public function managerOnly(): static
    {
        return $this->state(fn (): array => ['visible_to' => DocumentVisibility::Manager]);
    }

    public function employeeOnly(): static
    {
        return $this->state(fn (): array => ['visible_to' => DocumentVisibility::Employee]);
    }

    public function expiring(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->addDays(21)]);
    }
}
