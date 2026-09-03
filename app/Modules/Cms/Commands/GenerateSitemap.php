<?php

namespace App\Modules\Cms\Commands;

use App\Modules\Cms\Services\SitemapGenerator;
use Illuminate\Console\Command;

/**
 * sitemap:generate (07-queues-scheduling.md §3, nightly 02:00) — also
 * invoked on publish events (least-deviation resolution #5 in the
 * worklog: do both). Writes sitemap_index.xml + children to public/.
 */
class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate sitemap_index.xml and child sitemaps into public/';

    public function handle(SitemapGenerator $generator): int
    {
        $written = $generator->write();

        $this->info('Sitemaps written: '.implode(', ', $written));

        return self::SUCCESS;
    }
}
