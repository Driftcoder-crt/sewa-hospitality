<?php

namespace App\Modules\Careers\Listeners;

use App\Modules\Careers\Enums\ApplicationStatus;
use App\Modules\Careers\Events\ApplicationStatusChanged;
use App\Modules\Careers\Mail\ApplicationStatusMail;
use App\Support\Mail\Jobs\SendTemplateMail;
use App\Support\Queue\QueueHardenedListener;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * ATS stage change → candidate status email (06-hr §5) on the catalog
 * stages (screening/shortlisted/interview/offer/rejected). hired and
 * withdrawn close the loop person-to-person — no automated mail.
 */
class SendApplicationStatusEmail implements ShouldQueue
{
    use QueueHardenedListener;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600];

    public function handle(ApplicationStatusChanged $event): void
    {
        $application = $event->application;

        if (! in_array($event->to, ApplicationStatus::emailsOn(), true)) {
            return;
        }

        SendTemplateMail::dispatch(
            key: "application.status:{$application->getKey()}:{$event->to->value}",
            template: 'application.status',
            mailable: new ApplicationStatusMail($application, $event->to->label()),
        );
    }
}
