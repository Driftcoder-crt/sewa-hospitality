<?php

namespace App\Modules\Billing\Commands;

use App\Modules\Billing\Mail\InvoiceReminderMail;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Organizations\Models\Organization;
use App\Support\Mail\Jobs\SendTemplateMail;
use Illuminate\Console\Command;

/**
 * Polite reminder ladder (12-billing-finance §4.2/§5): day +3/+10/+20
 * past due, MAX 3 reminders then a human outreach task — never
 * automated spam. Each send carries a deterministic key
 * ("invoice.reminder:{id}:{n}") so retries and re-runs never double-send.
 */
class SendInvoiceReminders extends Command
{
    /** @var list<int> days past due */
    private const LADDER = [3, 10, 20];

    protected $signature = 'billing:reminders';

    protected $description = 'Queue polite payment reminders for open invoices (+3/+10/+20 days past due, max 3).';

    public function handle(): int
    {
        $queued = 0;

        $invoices = Invoice::query()
            ->outstanding()
            ->whereNotNull('due_at')
            ->where('due_at', '<', now()->subDays(2)->toDateString())
            ->whereColumn('reminders_sent', '<', 3)
            ->with('organization')
            ->get();

        foreach ($invoices as $invoice) {
            if (! $invoice->canRemind()) {
                continue;
            }

            $daysPast = now()->diffInDays($invoice->due_at);
            $step = $this->currentStep((int) $daysPast, $invoice->reminders_sent);

            if ($step === null) {
                continue;
            }

            $recipient = $this->billingContact($invoice->organization);

            if ($recipient === null) {
                $this->warn("Invoice {$invoice->number}: organization has no billing contact — skipped.");

                continue;
            }

            SendTemplateMail::dispatch(
                key: "invoice.reminder:{$invoice->getKey()}:{$step}",
                template: 'invoice.reminder',
                mailable: new InvoiceReminderMail($invoice, (int) $daysPast),
            )->onQueue('emails');

            $invoice->forceFill([
                'reminders_sent' => $invoice->reminders_sent + 1,
                'last_reminder_at' => now(),
            ])->save();

            $queued++;
        }

        $this->info("Reminders queued: {$queued}.");

        return self::SUCCESS;
    }

    /** Which ladder rung is due now (1, 2 or 3). */
    private function currentStep(int $daysPast, int $remindersSent): ?int
    {
        foreach (self::LADDER as $index => $day) {
            if ($daysPast >= $day && $remindersSent <= $index) {
                return $index + 1;
            }
        }

        return null;
    }

    /** Billing contact: org billing_address email, else the org's owner. */
    private function billingContact(?Organization $organization): ?string
    {
        $address = $organization?->billing_address ?? [];

        if (is_array($address) && ! empty($address['email'])) {
            return (string) $address['email'];
        }

        return $organization?->owner?->email;
    }
}
