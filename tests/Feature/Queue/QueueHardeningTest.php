<?php

use App\Support\Queue\QueueHardened;
use App\Support\Queue\QueueHardenedListener;
use Illuminate\Contracts\Queue\ShouldQueue;

/*
 * Queue-hardening contract (07-queues-scheduling + 05-security-reliability
 * §2): the Hostinger worker is cron-driven `queue:work --stop-when-empty`,
 * so a queued task without bounded retries is retried forever — it never
 * reaches failed_jobs and re-runs on every schedule:run cycle.
 *
 * This test walks every class under app/ that implements ShouldQueue and
 * enforces: explicit $tries >= 1, a backoff array (or a retryUntil()), a
 * failed() handler (own or via the QueueHardened trait), and that the
 * trait itself stays property-free (a same-name trait/class property
 * with different defaults is a FATAL error in PHP — the exact trap this
 * contract exists to prevent).
 */

function queuedTaskClasses(): array
{
    $found = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(app_path(), RecursiveDirectoryIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $relative = substr($file->getPathname(), strlen(app_path()) + 1, -4);
        $class = 'App\\'.str_replace('/', '\\', $relative);

        if (! class_exists($class)) {
            continue;
        }

        if (is_subclass_of($class, ShouldQueue::class)) {
            $found[] = $class;
        }
    }

    sort($found);

    return $found;
}

it('discovers the queued tasks under app/', function () {
    expect(count(queuedTaskClasses()))->toBeGreaterThanOrEqual(16);
});

it('bounds every queued job and listener with retries and a failed handler', function () {
    $tasks = queuedTaskClasses();

    expect($tasks)->not()->toBeEmpty();

    foreach ($tasks as $task) {
        $ref = new ReflectionClass($task);
        $defaults = $ref->getDefaultProperties();

        expect(array_key_exists('tries', $defaults))
            ->toBeTrue("{$task} must define \$tries — unbounded retries zombie-loop the cron worker");
        expect($defaults['tries'])->toBeInt()->toBeGreaterThanOrEqual(1);

        $hasBackoff = array_key_exists('backoff', $defaults)
            && is_array($defaults['backoff'])
            && $defaults['backoff'] !== [];
        $hasRetryUntil = $ref->hasMethod('retryUntil');

        expect($hasBackoff || $hasRetryUntil)
            ->toBeTrue("{$task} must define \$backoff or retryUntil()");

        expect($ref->hasMethod('failed'))
            ->toBeTrue("{$task} must have a failed() handler (own or QueueHardened trait)");
    }
});

it('keeps the hardening traits property-free — trait/class property collisions fatal', function () {
    foreach ([QueueHardened::class, QueueHardenedListener::class] as $trait) {
        $defaults = (new ReflectionClass($trait))->getDefaultProperties();

        expect($defaults)->not()->toHaveKey('tries')
            ->and($defaults)->not()->toHaveKey('backoff');
    }
});

it('applies the hardening traits to at least the standard tasks', function () {
    $users = 0;
    $hardening = [QueueHardened::class, QueueHardenedListener::class];

    foreach (queuedTaskClasses() as $task) {
        if (array_intersect($hardening, class_uses_recursive($task)) !== []) {
            $users++;
        }
    }

    expect($users)->toBeGreaterThanOrEqual(15);
});
