<?php

namespace App\Modules\Leads\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Leads\Services\NewsletterService;
use Illuminate\Contracts\View\View;

/**
 * Double opt-in confirm + one-click unsubscribe (03-leads-crm §3).
 * Both pages are honest noindex utility surfaces with clear next steps.
 */
class NewsletterController extends Controller
{
    public function __construct(private readonly NewsletterService $newsletter) {}

    public function confirm(string $token): View
    {
        $result = $this->newsletter->confirm($token);

        return view('leads.newsletter-status', [
            'mode' => 'confirm',
            'found' => $result['subscriber'] !== null,
            'status' => $result['subscriber']?->status?->label(),
            'fresh' => $result['fresh'],
        ]);
    }

    public function unsubscribe(string $token): View
    {
        $subscriber = $this->newsletter->unsubscribe($token);

        return view('leads.newsletter-status', [
            'mode' => 'unsubscribe',
            'found' => $subscriber !== null,
            'status' => $subscriber?->status?->label(),
        ]);
    }
}
