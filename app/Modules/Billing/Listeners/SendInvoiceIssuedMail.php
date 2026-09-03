<?php

namespace App\Modules\Billing\Listeners;

use App\Modules\Billing\Events\InvoiceIssued;
use App\Modules\Billing\Mail\InvoiceIssuedMail;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Services\InvoiceService;
use App\Modules\Organizations\Models\OrganizationUser;
use App\Modules\Portal\Services\PortalNotificationCenter;
use App\Support\Mail\Jobs\SendTemplateMail;
use App\Support\Queue\QueueHardenedListener;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * InvoiceIssued (12 doc §7): invoice.issued email with the immutable
 * PDF attached to the org's billing contact + portal notification rows
 * for managers and billing users. Never sends to nobody — a missing
 * billing contact surfaces in the ops digest instead of a lost email.
 */
class SendInvoiceIssuedMail implements ShouldQueue
{
    use QueueHardenedListener;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600];

    public function __construct(private readonly PortalNotificationCenter $notifications) {}

    public function handle(InvoiceIssued $event): void
    {
        $invoice = $event->invoice->refresh()->loadMissing(['organization.owner']);
        $service = app(InvoiceService::class);

        // The snapshot must exist before the mailable is queued — the
        // attachment reads from the private disk.
        $service->renderPdf($invoice);

        $recipient = $this->billingContact($invoice);

        if ($recipient !== null) {
            SendTemplateMail::dispatch(
                key: "invoice.issued:{$invoice->getKey()}:{$recipient}",
                template: 'invoice.issued',
                mailable: new InvoiceIssuedMail(
                    invoice: $invoice,
                    pdfPath: InvoiceService::snapshotPath($invoice),
                    portalUrl: route('portal.invoices.show', $invoice),
                ),
            );
        }

        foreach ($this->orgWideUserIds((string) $invoice->organization_id) as $userId) {
            $this->notifications->notify(
                userId: (string) $userId,
                title: 'Invoice '.$invoice->number.' issued',
                body: $invoice->formattedTotal(),
                url: '/invoices/'.$invoice->getKey(),
                kind: 'invoice',
            );
        }
    }

    private function billingContact(Invoice $invoice): ?string
    {
        $address = $invoice->organization?->billing_address ?? [];

        if (is_array($address) && ! empty($address['email'])) {
            return (string) $address['email'];
        }

        return $invoice->organization?->owner?->email;
    }

    private function orgWideUserIds(string $organizationId): array
    {
        return OrganizationUser::query()
            ->where('organization_id', $organizationId)
            ->whereIn('role_in_org', ['manager', 'billing'])
            ->pluck('user_id')
            ->all();
    }
}
