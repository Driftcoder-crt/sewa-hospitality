<?php

namespace App\Modules\Leads\Events;

use App\Modules\Leads\Models\NewsletterSubscriber;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Double opt-in completed — the subscriber is now marketable. */
class NewsletterSubscribed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly NewsletterSubscriber $subscriber) {}
}
