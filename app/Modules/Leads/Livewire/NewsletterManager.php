<?php

namespace App\Modules\Leads\Livewire;

use App\Modules\Leads\Enums\NewsletterStatus;
use App\Modules\Leads\Mail\NewsletterIssueMail;
use App\Modules\Leads\Models\NewsletterSubscriber;
use App\Modules\Leads\Services\NewsletterService;
use App\Support\Audit\ActivityLogger;
use App\Support\Locks\CircuitBreaker;
use App\Support\Mail\Jobs\SendTemplateMail;
use App\Support\Mail\MailLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Newsletter manager (03-leads-crm §4.5): subscribers + statuses +
 * bounces, confirm-rate stats, and the issue composer (markdown →
 * queued send to CONFIRMED subscribers only). Marketing sends refuse to
 * run while the mail breaker is open (10-email §5) — transactional
 * takes priority.
 */
#[Layout('layouts.admin')]
class NewsletterManager extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    /** Composer state. */
    public string $issueSubject = '';

    public string $issueBody = '';

    public bool $showComposer = false;

    public function updateStatusFilter(): void
    {
        $this->resetPage();
    }

    /** Explicit resend — clears the old log key so the mail can re-run. */
    public function resendConfirm(string $subscriberId): void
    {
        $this->authorize('update', Lead::class);

        $subscriber = NewsletterSubscriber::query()->findOrFail($subscriberId);

        if ($subscriber->status !== NewsletterStatus::Pending) {
            $this->dispatch('notify', tone: 'error', message: 'Only pending subscriptions can be re-confirmed.');

            return;
        }

        MailLog::query()->where('key', "newsletter.confirm:{$subscriber->getKey()}")->delete();
        app(NewsletterService::class)->sendConfirm($subscriber);

        ActivityLogger::log('admin', 'update', $subscriber, ['action' => 'resend_confirm']);
        $this->dispatch('notify', tone: 'success', message: 'Confirmation email re-queued.');
    }

    /** Manual unsubscribe (bounced/list hygiene). */
    public function unsubscribe(string $subscriberId): void
    {
        $this->authorize('update', Lead::class);

        $subscriber = NewsletterSubscriber::query()->findOrFail($subscriberId);
        $subscriber->forceFill([
            'status' => NewsletterStatus::Unsubscribed,
            'unsubscribed_at' => now(),
        ])->save();

        ActivityLogger::log('admin', 'update', $subscriber, ['action' => 'unsubscribe']);
        $this->dispatch('notify', tone: 'success', message: 'Subscriber unsubscribed.');
    }

    public function openComposer(): void
    {
        $this->showComposer = true;
    }

    /** Queue the issue to every confirmed subscriber (chunked jobs). */
    public function sendIssue(): void
    {
        $this->authorize('update', Lead::class);

        $this->validate([
            'issueSubject' => ['required', 'string', 'min:4', 'max:120'],
            'issueBody' => ['required', 'string', 'min:20', 'max:20000'],
        ]);

        if (CircuitBreaker::isOpen('mail')) {
            $this->dispatch('notify', tone: 'error', message: 'Email provider is degraded — marketing sends are paused. Try again after ops clears the breaker.');

            return;
        }

        $confirmed = NewsletterSubscriber::query()->where('status', NewsletterStatus::Confirmed)->get();

        if ($confirmed->isEmpty()) {
            $this->dispatch('notify', tone: 'error', message: 'No confirmed subscribers yet.');

            return;
        }

        $issueId = (string) Str::ulid();
        $html = Str::markdown($this->issueBody);

        foreach ($confirmed as $subscriber) {
            SendTemplateMail::dispatch(
                key: "newsletter.issue:{$issueId}:{$subscriber->getKey()}",
                template: 'newsletter.issue',
                mailable: new NewsletterIssueMail($subscriber, $this->issueSubject, $html),
                marketing: true,
            );
        }

        ActivityLogger::log('admin', 'create', null, [
            'action' => 'newsletter_issue', 'issue' => $issueId,
            'subject' => $this->issueSubject, 'recipients' => $confirmed->count(),
        ]);

        $this->reset('issueSubject', 'issueBody', 'showComposer');
        $this->dispatch('notify', tone: 'success', message: $confirmed->count().' issue email(s) queued.');
    }

    public function render(): View
    {
        $this->authorize('viewAny', Lead::class);

        $subscribers = NewsletterSubscriber::query()
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('created_at')
            ->paginate(20);

        $stats = NewsletterSubscriber::query()
            ->selectRaw('status, count(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status');

        $total = (int) $stats->sum();
        $confirmed = (int) ($stats[NewsletterStatus::Confirmed->value] ?? 0);
        $pending = (int) ($stats[NewsletterStatus::Pending->value] ?? 0);
        $unsubscribed = (int) ($stats[NewsletterStatus::Unsubscribed->value] ?? 0);
        $bounced = (int) ($stats[NewsletterStatus::Bounced->value] ?? 0);

        return view('leads.livewire.newsletter-manager', [
            'subscribers' => $subscribers,
            'statuses' => NewsletterStatus::options(),
            'stats' => [
                'total' => $total,
                'confirmed' => $confirmed,
                'pending' => $pending,
                'unsubscribed' => $unsubscribed,
                'bounced' => $bounced,
                'confirm_rate' => ($pending + $confirmed) > 0
                    ? (int) round($confirmed / max(1, $pending + $confirmed) * 100)
                    : 0,
            ],
        ]);
    }
}
