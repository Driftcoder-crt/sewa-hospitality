<?php

namespace App\Modules\Portal\Commands;

use App\Modules\Portal\Models\PortalChecklistItem;
use App\Modules\Portal\Models\PortalDocument;
use App\Modules\Portal\Models\PortalThread;
use App\Modules\Portal\Services\PortalAudience;
use App\Modules\Portal\Services\PortalNotificationCenter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * portal:housekeeping — daily (04 doc §5 + §8 tests):
 *
 *  1. Checklist items due within 48h → notification rows for the
 *     move's employee + consultant (due-date awareness).
 *  2. Documents expiring within 30d (visa/lease) → notification rows
 *     for the employee + org managers (expiry reminders).
 *  3. Threads idle beyond the chat SLA (72h) → flagged to the ops
 *     cache for the ops digest (04 doc §5 chat SLA).
 *
 * Each notification carries a deterministic day-bucket kind so
 * re-runs never spam (the notification exists for "due:Y-m-d" titles —
 * deduped by title+user+day before insert).
 */
class PortalHousekeeping extends Command
{
    /** Thread idle threshold treated as an SLA miss. */
    private const CHAT_SLA_HOURS = 72;

    protected $signature = 'portal:housekeeping';

    protected $description = 'Checklist due reminders, document expiry warnings, chat SLA flags (daily).';

    public function handle(PortalNotificationCenter $notifications): int
    {
        $this->remindDueChecklist($notifications);
        $this->remindExpiringDocuments($notifications);
        $this->flagIdleThreads();

        return self::SUCCESS;
    }

    private function remindDueChecklist(PortalNotificationCenter $notifications): void
    {
        $items = PortalChecklistItem::query()
            ->where('status', 'pending')
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [now()->startOfDay(), now()->addDays(2)->endOfDay()])
            ->with(['move.employee', 'move.consultant'])
            ->get();

        foreach ($items as $item) {
            $move = $item->move;

            $targets = array_filter([
                $move?->employee_user_id,
                $move?->primary_consultant_user_id,
            ]);

            foreach ($targets as $userId) {
                $this->notifyOnce(
                    $notifications,
                    (string) $userId,
                    'Task due soon: '.$item->title,
                    ($move?->reference ?? 'Your move').' — due '.$item->due_at->format('d M'),
                    '/moves/'.($move?->getKey() ?? ''),
                    'checklist',
                    'checklist:'.$item->getKey().':'.$item->due_at->format('Ymd'),
                );
            }
        }
    }

    private function remindExpiringDocuments(PortalNotificationCenter $notifications): void
    {
        $documents = PortalDocument::query()
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(30)])
            ->with(['move.employee'])
            ->get();

        foreach ($documents as $document) {
            $move = $document->move;

            $targets = PortalAudience::orgWideUserIds((string) $document->organization_id);

            if ($move?->employee_user_id !== null) {
                $targets[] = $move->employee_user_id;
            }

            foreach (array_unique($targets) as $userId) {
                $this->notifyOnce(
                    $notifications,
                    (string) $userId,
                    'Document expiring: '.$document->title,
                    'Valid until '.$document->expires_at->format('d M Y'),
                    '/moves/'.($move?->getKey() ?? '').'/documents',
                    'document',
                    'docexpiry:'.$document->getKey().':'.$document->expires_at->format('Ym'),
                );
            }
        }
    }

    private function flagIdleThreads(): void
    {
        // Open threads whose newest message is older than the chat SLA
        // (04 doc §5: consultant reply windows — ops sees the misses).
        $idle = PortalThread::query()
            ->open()
            ->with('messages')
            ->get()
            ->filter(fn (PortalThread $thread) => $thread->lastMessage() !== null
                && $thread->lastMessage()->created_at->lessThan(now()->subHours(self::CHAT_SLA_HOURS)));

        Cache::put('sewa.portal.idle_threads', $idle->pluck('id')->all(), 86400);
        Cache::put('sewa.portal.idle_threads.count', $idle->count(), 86400);
    }

    /** Day-scoped dedupe — the same reminder never lands twice. */
    private function notifyOnce(
        PortalNotificationCenter $notifications,
        string $userId,
        string $title,
        string $body,
        string $url,
        string $kind,
        string $dedupeKey,
    ): void {
        $key = 'sewa.notified:'.$dedupeKey.':'.$userId.':'.now()->format('Ymd');

        if (Cache::has($key)) {
            return;
        }

        $notifications->notify($userId, $title, $body, $url, $kind);

        Cache::put($key, true, now()->addDay());
    }
}
