<?php

namespace App\Modules\Csr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Modules\Csr\Models\CsrStory;
use App\Modules\Csr\Models\NgoPartner;
use App\Modules\I18n\Services\ContentVariants;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public CSR surface (09 doc §3): /csr program page (partners with
 * real link-outs + claims with as-of + stories feed) and citable
 * story pages. Only active partnerships display; archived render in
 * the "past associations" collapsed list.
 */
class CsrController extends Controller
{
    public function index(): View
    {
        $active = NgoPartner::query()
            ->where('status', 'active')
            ->with('logo')
            ->orderBy('sort')
            ->get();

        $past = NgoPartner::query()
            ->where('status', 'archived')
            ->with('logo')
            ->orderBy('sort')
            ->get();

        $stories = CsrStory::query()
            ->published()
            ->with('partner')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        return view('csr.index', [
            'active' => $active,
            'past' => $past,
            'stories' => $stories,
        ]);
    }

    public function story(string $slug): View
    {
        $story = ContentVariants::firstInLocale(
            CsrStory::query()->published()->where('slug', $slug),
        )?->load('partner');

        if (! $story) {
            throw new NotFoundHttpException('Story not found.');
        }

        return view('csr.story', [
            'story' => $story,
            'media' => Media::query()->whereIn('id', (array) ($story->media_ids ?? []))->get(),
        ]);
    }
}
