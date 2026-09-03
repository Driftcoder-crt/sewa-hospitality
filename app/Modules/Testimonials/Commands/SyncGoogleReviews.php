<?php

namespace App\Modules\Testimonials\Commands;

use App\Modules\Testimonials\Services\GbpSyncService;
use App\Modules\Testimonials\Services\ReviewRequestEngine;
use Illuminate\Console\Command;

/**
 * reviews:sync-gbp — daily 06:00 (07-queues-scheduling §3, 08 doc
 * §4.2): pull Google reviews into the idempotent cache + process the
 * single follow-up pass of the review-request engine.
 */
class SyncGoogleReviews extends Command
{
    protected $signature = 'reviews:sync-gbp';

    protected $description = 'Sync Google Business Profile reviews + run review-request follow-ups (daily 06:00)';

    public function handle(GbpSyncService $gbp, ReviewRequestEngine $engine): int
    {
        $result = $gbp->sync();

        $followUps = $engine->processFollowUps();

        $this->info(sprintf(
            'reviews:sync-gbp — %d imported, %d recovery alert(s), %d follow-up(s)%s.',
            $result['imported'],
            $result['recovered'],
            $followUps,
            $result['skipped'] ? ' (skipped: connector not configured)' : '',
        ));

        return self::SUCCESS;
    }
}
