<?php

namespace App\Console\Commands;

use App\Modules\Ai\Models\AiInvocation;
use App\Modules\Ai\Services\AiBudget;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Blog\Models\Post;
use App\Modules\Cities\Models\City;
use App\Modules\Cms\Models\Page;
use App\Modules\Csr\Models\CsrStory;
use App\Modules\I18n\Models\Translation;
use App\Modules\Leads\Enums\LeadEventType;
use App\Modules\Leads\Models\Lead;
use App\Modules\Leads\Models\LeadEvent;
use App\Modules\Search\Models\SearchQuery;
use App\Modules\Services\Models\Service;
use App\Support\Mail\Jobs\SendTemplateMail;
use App\Support\Mail\OpsDigestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ops:digest — daily 09:00 (03-technical-specs/10-email.md §4): leads,
 * SLA breaches, failed jobs, queue depth, zero-result searches. The
 * "never silent" rule for the ops rhythm — the email IS the ops queue.
 */
class OpsDigest extends Command
{
    protected $signature = 'ops:digest';

    protected $description = 'Compose and queue the daily ops digest email (09:00)';

    public function handle(): int
    {
        $since = now()->subDay();

        $newLeads = Lead::query()
            ->where('created_at', '>=', $since)->count();

        $breaches = LeadEvent::query()
            ->where('type', LeadEventType::System->value)
            ->where('payload->kind', 'sla_breached')
            ->where('created_at', '>=', $since)->count();

        $failedJobs = DB::table('failed_jobs')->where('failed_at', '>=', $since)->count();

        $queueDepth = DB::table('jobs')->count();
        $oldestJob = DB::table('jobs')->min('created_at');
        $oldestJobMinutes = $oldestJob ? (int) now()->diffInMinutes(now()->createFromTimestamp($oldestJob)) : 0;

        $zeroResults = SearchQuery::query()
            ->where('zero_results', true)
            ->orderByDesc('count')
            ->take(5)
            ->get(['term', 'count']);

        $lines = [
            "{$newLeads} new lead(s) in the last 24h.",
            $breaches > 0 ? "⚠ {$breaches} SLA breach(es) detected." : 'No SLA breaches.',
            $failedJobs > 0 ? "⚠ {$failedJobs} failed job(s) need retry." : 'No failed jobs.',
        ];

        // Portal chat SLA (04-client-portal §5): open threads idle beyond
        // the SLA window — flagged hourly by portal:housekeeping.
        $idleThreads = (int) (cache()->get('sewa.portal.idle_threads.count', 0));

        // Billing exposure (12-billing-finance §4.3): outstanding aging
        // buckets keep finance honest in the same daily rhythm.
        $outstanding = Invoice::query()->outstanding()->get(['total']);
        $outstandingSum = $outstanding->sum('total');
        $overdueSum = Invoice::query()->where('status', 'overdue')->get(['total'])->sum('total');

        $sections = [
            'Leads (24h)' => (string) $newLeads,
            'SLA breaches (24h)' => (string) $breaches,
            'Failed jobs (24h)' => (string) $failedJobs,
            'Queue depth' => $queueDepth." job(s), oldest {$oldestJobMinutes} min",
            'Zero-result searches' => $zeroResults->isEmpty()
                ? 'None'
                : $zeroResults->map(fn ($row): string => "{$row->term} (×{$row->count})")->implode(', '),
            'Portal chat SLA misses' => $idleThreads > 0
                ? "⚠ {$idleThreads} thread(s) idle beyond 72h"
                : 'None',
            'Outstanding invoices' => 'INR '.number_format($outstandingSum / 100, 2)
                .' (overdue: INR '.number_format($overdueSum / 100, 2).')',
            'AI (24h)' => $this->aiSummary(),
            'Translation queue' => $this->translationQueueSummary(),
        ];

        if ($idleThreads > 0) {
            $lines[] = "⚠ {$idleThreads} portal thread(s) idle beyond the chat SLA — consultant follow-up needed.";
        }

        $opsEmails = (array) config('sewa.emails.ops', []);

        if ($opsEmails === []) {
            $this->warn('No ops recipients configured (SEWA_OPS_EMAILS) — digest skipped.');

            return self::SUCCESS;
        }

        foreach ($opsEmails as $email) {
            SendTemplateMail::dispatch(
                key: 'ops.digest:'.now()->format('Ymd').':'.$email,
                template: 'ops.digest',
                mailable: (new OpsDigestMail($sections, $lines))->to($email),
            );
        }

        $this->info('ops:digest queued to '.count($opsEmails).' recipient(s).');

        return self::SUCCESS;
    }

    /** AI rhythm (08-ai-system/01 §4 monitoring): calls, failures, budget ratios. */
    private function aiSummary(): string
    {
        $since = now()->subDay();

        $total = AiInvocation::query()->since($since)->count();
        $errors = AiInvocation::query()->since($since)->withStatus('error')->count();

        if ($total === 0) {
            return 'No AI calls.';
        }

        $overBudget = collect(config('ai.features', []))
            ->filter(fn (array $config, string $feature): bool => (int) ($config['budget_tokens'] ?? 0) > 0
                && AiBudget::usage($feature)['token_ratio'] >= (float) config('ai.budget_alert_ratio', 0.8))
            ->keys()
            ->all();

        $summary = "{$total} call(s), {$errors} failure(s)";

        return $overBudget !== []
            ? "⚠ {$summary} — budget ≥80%: ".implode(', ', $overBudget)
            : $summary;
    }

    /** Machine-draft depth (11-multilingual §6.2): reviews waiting on humans. */
    private function translationQueueSummary(): string
    {
        $drafts = 0;

        foreach ([Page::class, Service::class,
            City::class, Post::class,
            CsrStory::class,
        ] as $entityClass) {
            $drafts += $entityClass::query()->whereNotNull('locale_source_id')->where('status', 'draft')->count();
        }

        $strings = Translation::query()->machine()->count();

        return "{$drafts} content draft(s), {$strings} UI string(s) awaiting review";
    }
}
