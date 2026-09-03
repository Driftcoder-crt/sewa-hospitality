<?php

namespace Database\Seeders;

use App\Modules\Blog\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Editorial taxonomy seed (03-database-schema.md §13 + 04-modules/
 * 07-blog-news.md §4.4): the reference's 15 category equivalents plus
 * Sewa's documented additions. Editable archive intros stay empty —
 * editors write them (no invented copy in prod seeds).
 */
class CategoriesSeeder extends Seeder
{
    /**
     * slug => [name, parent slug or null]. Order is the sidebar sort.
     *
     * @var array<string, array{0: string, 1: string|null}>
     */
    protected array $taxonomy = [
        'expat-news' => ['Expat News', null],
        'lifestyle-in-india' => ['Lifestyle in India', null],
        'relocation' => ['Relocation', null],
        'moving' => ['Moving', 'relocation'],
        'visa-immigration-news' => ['Visa & Immigration News', null],
        'corporate-housing' => ['Corporate Housing', null],
        'health-safety' => ['Health & Safety', null],
        'global-mobility' => ['Global Mobility', null],
        'fleet' => ['Fleet', null],
        'news' => ['News', null],
        'expat-in-india' => ['Expat in India', null],
        'relocation-guide-to-india' => ['Relocation Guide to India', 'relocation'],
        'uncategorized' => ['Uncategorized', null],
        // Sewa additions (07-blog-news + schema §13):
        'city-guides' => ['City Guides', null],
        'immigration-explainers' => ['Immigration Explainers', 'visa-immigration-news'],
        'housing-market-notes' => ['Housing Market Notes', 'corporate-housing'],
    ];

    public function run(): void
    {
        $ids = [];

        // Parents first (order preserved by declaration).
        foreach ($this->taxonomy as $slug => [$name, $parent]) {
            $category = Category::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'parent_id' => $parent !== null ? ($ids[$parent] ?? null) : null,
                    'locale' => 'en',
                    'sort' => count($ids),
                ],
            );

            $ids[$slug] = $category->getKey();
        }
    }
}
