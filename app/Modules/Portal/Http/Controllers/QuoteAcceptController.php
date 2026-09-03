<?php

namespace App\Modules\Portal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Models\Quote;
use App\Modules\Billing\Services\QuoteService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Quote acceptance (12 doc §3): emailed secure token link — works for
 * logged-in org members AND tokenized recipients. Decisions are
 * single-use + expiry-bound (enforced by QuoteService::decideByToken).
 */
class QuoteAcceptController extends Controller
{
    public function show(Request $request, string $quote, string $token): View|RedirectResponse
    {
        $model = Quote::query()->findOrFail($quote);

        // A mismatched token gets the same page as an unknown one —
        // no existence confirmation for wrong links.
        if (! hash_equals((string) ($model->token ?? ''), $token)) {
            return view('portal.quotes.invalid');
        }

        return view('portal.quotes.accept', [
            'quote' => $model->loadMissing(['organization', 'move']),
            'token' => $token,
            'decision' => session('decision'),
        ]);
    }

    public function decide(Request $request, string $quote, string $token, QuoteService $quotes): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:accept,reject'],
        ]);

        $model = Quote::query()->findOrFail($quote);

        $actor = auth()->user()?->email;

        try {
            $quotes->decideByToken($token, $validated['decision'] === 'accept', $actor);
        } catch (ValidationException $e) {
            return redirect()
                ->route('portal.quotes.accept', ['quote' => $quote, 'token' => $token])
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('portal.quotes.accept', ['quote' => $quote, 'token' => $token])
            ->with('decision', $validated['decision']);
    }
}
