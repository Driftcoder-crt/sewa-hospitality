<?php

namespace App\Modules\Cms\Commands;

use App\Modules\Cms\Enums\PageStatus;
use App\Modules\Cms\Events\PagePublished;
use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Services\PublishGate;
use App\Support\Audit\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Scheduled publishes (04-modules/01-cms.md §5): cron-driven — the
 * single schedule:run hit fires this every minute; pages whose
 * scheduled_at has passed are published IF the publish gate passes
 * (a scheduled page that fails the gate stays scheduled and is
 * reported — never published broken, never silently dropped).
 */
class PublishScheduledPages extends Command
{
    protected $signature = 'cms:publish-scheduled {--dry-run : Report what would publish}';

    protected $description = 'Publish CMS pages whose scheduled_at time has arrived (publish-gated)';

    public function handle(PublishGate $gate): int
    {
        $due = Page::query()
            ->where('status', PageStatus::Scheduled->value)
            ->where('scheduled_at', '<=', Carbon::now())
            ->orderBy('scheduled_at')
            ->get();

        if ($due->isEmpty()) {
            $this->info('No scheduled pages are due.');

            return self::SUCCESS;
        }

        foreach ($due as $page) {
            $inspection = $gate->inspect($page);

            if ($inspection['errors'] !== []) {
                $this->warn("{$page->slug}: still blocked — ".implode('; ', array_values($inspection['errors'])));
                ActivityLogger::log('system', 'publish_blocked', $page, [
                    'reason' => 'scheduled publish failed the gate',
                    'errors' => array_keys($inspection['errors']),
                ]);

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("{$page->slug}: would publish");

                continue;
            }

            $page->status = PageStatus::Published;
            $page->published_at = Carbon::now();
            $page->save();
            $page->refresh();

            event(new PagePublished($page));

            $this->info("{$page->slug}: published");
            ActivityLogger::log('system', 'publish', $page, ['reason' => 'scheduled publish']);
        }

        return self::SUCCESS;
    }
}
