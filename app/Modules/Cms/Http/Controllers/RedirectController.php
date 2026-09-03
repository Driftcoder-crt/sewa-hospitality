<?php

namespace App\Modules\Cms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cms\Services\RedirectService;
use Illuminate\Http\Request;

/**
 * Route fallback (04-modules/01-cms.md §4.5): unknown paths consult
 * the redirects map first (301/302 + hit counter) — slug moves keep
 * their equity; everything else 404s through the never-dead-end error
 * template.
 */
class RedirectController extends Controller
{
    public function __invoke(Request $request)
    {
        $redirect = RedirectService::lookup($request->path());

        if ($redirect && $redirect->active) {
            $redirect->hit();

            return redirect()
                ->to($redirect->to, $redirect->code->value)
                ->header('Cache-Control', 'public, max-age=86400');
        }

        abort(404);
    }
}
