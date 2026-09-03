<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| SEWA scheduler (03-technical-specs/07-queues-scheduling.md §3)
|--------------------------------------------------------------------------
| ONE hPanel cron hits `php artisan schedule:run` every minute; the
| entries below do everything else. Overlap safety is mandatory on
| shared hosting (error-locks doctrine §2.4).
*/

// Heartbeat for the /status page + UptimeRobot (<90s staleness check).
Schedule::call(function (): void {
    cache()->put('sewa.scheduler.heartbeat', now()->toIso8601String(), 300);
})->name('scheduler.heartbeat')->everyMinute();

// Drain the database queue in short bursts — twice per minute
// (user-facing jobs within 1–2 min; each run well under max_execution_time).
Schedule::command('queue:work --stop-when-empty --queue=default,emails --max-time=45 --sleep=1')
    ->name('drain-default-emails')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('queue:work --stop-when-empty --queue=ai,syncs,exports --max-time=45 --sleep=1')
    ->name('drain-ai-syncs-exports')
    ->everyMinute()
    ->withoutOverlapping();

// Laravel Pulse housekeeping (database driver; recorder loop).
Schedule::command('pulse:check')
    ->name('pulse.check')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Backups (06-hosting-deployment §8): nightly dump + morning verification.
Schedule::command('db:backup')
    ->name('db.backup')
    ->dailyAt('01:30')
    ->withoutOverlapping();

Schedule::command('backups:verify')
    ->name('backups.verify')
    ->dailyAt('08:00');

// Media hygiene (09-media-pipeline §7): orphan conversions sweep.
Schedule::command('media:prune')
    ->name('media.prune')
    ->weeklyOn(0, '03:00')
    ->withoutOverlapping();

// Scheduled page publishes (04-modules/01-cms.md §5, cron-driven).
Schedule::command('cms:publish-scheduled')
    ->name('cms.publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping();

// Sitemap (06-content-seo/02-seo-technical §3): nightly 02:00 safety
// net + regenerate on publish events (listener queues the same job).
Schedule::command('sitemap:generate')
    ->name('sitemap.generate')
    ->dailyAt('02:00')
    ->withoutOverlapping();

// SEO audit (07-queues-scheduling §3): nightly 04:00 hygiene mirror.
Schedule::command('seo:audit')
    ->name('seo.audit')
    ->dailyAt('04:00')
    ->withoutOverlapping();

// SLA monitor (03-leads-crm §4.4): hourly breach detection +
// unassigned escalation (15 min threshold) → system events + alerts.
Schedule::command('sla:calculate')
    ->name('sla.calculate')
    ->hourly()
    ->withoutOverlapping();

// DPDP retention (05-security-reliability §4): monthly anonymization
// of expired leads/applications, 1st at 03:00.
Schedule::command('retention:anonymize')
    ->name('retention.anonymize')
    ->monthlyOn(1, '03:00')
    ->withoutOverlapping();

// Ops digest (10-email §4): daily 09:00 rhythm — leads, SLA, queue,
// failed jobs, zero-result searches.
Schedule::command('ops:digest')
    ->name('ops.digest')
    ->dailyAt('09:00')
    ->withoutOverlapping();

// Scheduled post publishes (07-blog-news §5, cron-driven like the CMS).
Schedule::command('posts:publish-scheduled')
    ->name('posts.publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping();

// GBP reviews + review-request follow-ups (08 doc §4.2, 06:00 daily).
Schedule::command('reviews:sync-gbp')
    ->name('reviews.sync-gbp')
    ->dailyAt('06:00')
    ->withoutOverlapping();

// Portal housekeeping (04-client-portal §5/§8): checklist due
// reminders + document expiry warnings + chat SLA flags, daily 07:15.
Schedule::command('portal:housekeeping')
    ->name('portal.housekeeping')
    ->dailyAt('07:15')
    ->withoutOverlapping();

// Billing hygiene (12-billing-finance §4.2): overdue flip + quote
// expiry nightly; polite reminder ladder +3/+10/+20 (cap 3) at 07:45.
Schedule::command('billing:mark-overdue')
    ->name('billing.mark-overdue')
    ->dailyAt('07:00')
    ->withoutOverlapping();

Schedule::command('billing:reminders')
    ->name('billing.reminders')
    ->dailyAt('07:45')
    ->withoutOverlapping();
