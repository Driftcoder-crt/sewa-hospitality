<?php

namespace App\Modules\Portal\Listeners;

use App\Modules\Portal\Events\DocumentPublished;
use App\Modules\Portal\Mail\DocumentPublishedMail;
use App\Modules\Portal\Services\PortalAudience;
use App\Modules\Portal\Services\PortalNotificationCenter;
use App\Support\Mail\Jobs\SendTemplateMail;
use App\Support\Queue\QueueHardenedListener;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * DocumentPublished (04 doc §4.3/§7): the employee is notified by
 * email + notification row — with a portal link, NEVER an attachment
 * (error-lock: originals never leave the private disk).
 */
class NotifyDocumentPublished implements ShouldQueue
{
    use QueueHardenedListener;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600];

    public function __construct(private readonly PortalNotificationCenter $notifications) {}

    public function handle(DocumentPublished $event): void
    {
        $document = $event->document->refresh()->loadMissing(['move.employee', 'media']);
        $move = $document->move;

        if ($move?->employee === null) {
            return;
        }

        SendTemplateMail::dispatch(
            key: "document.published:{$document->getKey()}:{$move->employee->email}",
            template: 'document.published',
            mailable: new DocumentPublishedMail(
                document: $document,
                portalUrl: route('portal.documents', $move),
                recipientName: $move->employee->name,
                recipientEmail: $move->employee->email,
            ),
        );

        $this->notifications->notify(
            userId: (string) $move->employee->getKey(),
            title: 'New document: '.$document->title,
            body: $document->category?->label(),
            url: '/moves/'.$move->getKey().'/documents',
            kind: 'document',
        );

        // Org managers also get a silent notification row (they see the
        // documents tab anyway) — cheap, and the badge stays truthful.
        foreach (PortalAudience::orgWideUserIds((string) $move->organization_id) as $userId) {
            if ((string) $userId === (string) $move->employee->getKey()) {
                continue;
            }

            $this->notifications->notify(
                userId: (string) $userId,
                title: 'Document published on '.$move->reference,
                body: $document->title,
                url: '/moves/'.$move->getKey().'/documents',
                kind: 'document',
            );
        }
    }
}
