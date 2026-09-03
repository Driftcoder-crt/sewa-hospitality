<?php

namespace App\Modules\Careers\Listeners;

use App\Modules\Careers\Events\ApplicationReceived;
use App\Modules\Careers\Mail\ApplicationAckMail;
use App\Modules\Careers\Mail\ApplicationReceivedMail;
use App\Support\Mail\Jobs\SendTemplateMail;
use App\Support\Queue\QueueHardenedListener;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Application → emails (06-hr §7, 10-email §4): application.ack to the
 * candidate + application.received to careers@/recruiters with the 24h
 * signed resume link. Queued — the submit request only writes to DB.
 */
class SendApplicationNotifications implements ShouldQueue
{
    use QueueHardenedListener;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600];

    public function handle(ApplicationReceived $event): void
    {
        $application = $event->application;

        SendTemplateMail::dispatch(
            key: "application.ack:{$application->getKey()}",
            template: 'application.ack',
            mailable: (new ApplicationAckMail($application))->locale($application->posting?->locale ?? 'en'),
        );

        SendTemplateMail::dispatch(
            key: "application.received:{$application->getKey()}",
            template: 'application.received',
            mailable: new ApplicationReceivedMail(
                $application,
                // 24h signed link — long enough for recruiter review shifts.
                url()->temporarySignedRoute('admin.applications.resume', now()->addHours(24), [
                    'application' => $application->getKey(),
                ]),
            ),
        );
    }
}
