<?php

namespace App\Modules\Leads\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * /thank-you (03-leads-crm §3): the honest confirmation surface — SLA
 * promise, what happens next, portal teaser. Never a silent redirect
 * that loses typed data: the forms redirect here ONLY after a recorded
 * write. Noindex (utility page, not SEO surface).
 */
class ThankYouController extends Controller
{
    public function __invoke(): View
    {
        $source = (string) request()->query('source', 'contact');
        $source = in_array($source, ['contact', 'quote', 'callback'], true) ? $source : 'contact';

        $promises = [
            'contact' => 'A consultant will reply within 2 business hours.',
            'quote' => 'A proposal-ready reply within 4 business hours.',
            'callback' => 'A call back within 2 business hours — in your preferred window.',
        ];

        return view('leads.thank-you', [
            'source' => $source,
            'promise' => $promises[$source],
            'reference' => (string) request()->query('ref', ''),
        ]);
    }
}
