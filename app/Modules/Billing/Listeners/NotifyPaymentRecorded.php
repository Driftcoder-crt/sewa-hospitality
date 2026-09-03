<?php

namespace App\Modules\Billing\Listeners;

use App\Modules\Billing\Events\PaymentRecorded;
use App\Modules\Organizations\Models\OrganizationUser;
use App\Modules\Portal\Services\PortalNotificationCenter;
use App\Support\Queue\QueueHardenedListener;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * PaymentRecorded (12 doc §7): portal notification to the org's
 * manager/billing users. The thank-you note rides the portal
 * notification (a separate marketing-style email would be noise —
 * the etiquette rule keeps mail scarce).
 */
class NotifyPaymentRecorded implements ShouldQueue
{
    use QueueHardenedListener;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600];

    public function __construct(private readonly PortalNotificationCenter $notifications) {}

    public function handle(PaymentRecorded $event): void
    {
        $invoice = $event->invoice->refresh();

        foreach (OrganizationUser::query()
            ->where('organization_id', (string) $invoice->organization_id)
            ->whereIn('role_in_org', ['manager', 'billing'])
            ->pluck('user_id') as $userId) {
            $this->notifications->notify(
                userId: (string) $userId,
                title: 'Payment received for '.$invoice->number,
                body: $event->payment->formattedAmount().' — thank you.',
                url: '/invoices/'.$invoice->getKey(),
                kind: 'invoice',
            );
        }
    }
}
