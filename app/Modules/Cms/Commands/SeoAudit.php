<?php

namespace App\Modules\Cms\Commands;

use App\Modules\Cities\Models\City;
use App\Modules\Cities\Models\HousingUnit;
use App\Modules\Cms\Models\Page;
use App\Modules\Services\Models\Service;
use App\Support\Audit\ActivityLogger;
use Illuminate\Console\Command;

/**
 * seo:audit (07-queues-scheduling.md §3, nightly 04:00): honest,
 * cheap heuristics over published content — empty metas, titles over
 * guidance length, published noindex (needs a reason on file), housing
 * rate staleness. Results logged + surfaced on the ops pulse; failures
 * never block anything (an audit is a mirror, not a gate).
 */
class SeoAudit extends Command
{
    protected $signature = 'seo:audit';

    protected $description = 'Audit published content for SEO hygiene (metas, noindex reasons, stale rates)';

    public function handle(): int
    {
        $issues = [];

        Page::query()->published()->get()->each(function (Page $page) use (&$issues): void {
            if (trim((string) $page->meta_title) === '') {
                $issues[] = "page {$page->slug}: empty meta title";
            }
            if (mb_strlen((string) $page->meta_title) > 70) {
                $issues[] = "page {$page->slug}: meta title > 70 chars";
            }
            if (trim((string) $page->meta_description) === '') {
                $issues[] = "page {$page->slug}: empty meta description";
            }
            if ($page->noindex && trim((string) $page->noindex_reason) === '') {
                $issues[] = "page {$page->slug}: noindex without a logged reason";
            }
        });

        Service::query()->published()->get()->each(function (Service $service) use (&$issues): void {
            if (trim((string) $service->meta_title) === '') {
                $issues[] = "service {$service->slug}: empty meta title";
            }
            if ($service->hero_media_id === null) {
                $issues[] = "service {$service->slug}: published without hero media";
            }
        });

        City::query()->published()->get()->each(function (City $city) use (&$issues): void {
            if (trim((string) $city->meta_description) === '') {
                $issues[] = "city {$city->slug}: empty meta description";
            }
        });

        $staleUnits = HousingUnit::query()
            ->where('published', true)
            ->where('updated_at', '<', now()->subDays(90))
            ->count();
        if ($staleUnits > 0) {
            $issues[] = "housing: {$staleUnits} published unit(s) with rates older than 90 days";
        }

        foreach ($issues as $issue) {
            $this->warn($issue);
        }

        ActivityLogger::log('system', 'seo_audit', null, [
            'issues' => count($issues),
            'detail' => array_slice($issues, 0, 20),
        ]);

        $this->info($issues === [] ? 'SEO audit clean.' : 'SEO audit found '.count($issues).' issue(s).');

        return self::SUCCESS;
    }
}
