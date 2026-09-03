<?php

/*
|--------------------------------------------------------------------------
| /v1/health probe tests (04-api-spec.md §2, 06-hosting-deployment.md §7)
|--------------------------------------------------------------------------
| RefreshDatabase brings up every M0 migration (jobs/failed_jobs/cache
| included) on sqlite :memory:; the array cache store answers the cache
| probe. UptimeRobot + the deploy runbook consume this endpoint.
*/

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('health probe reports ok when database and cache respond', function (): void {
    $this->getJson('/v1/health')
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('checks.database', 'ok')
        ->assertJsonPath('checks.cache', 'ok')
        ->assertJsonPath('checks.queue.status', 'ok')
        ->assertJsonPath('scheduler_heartbeat_age_seconds', null)
        ->assertJsonStructure(['status', 'checks', 'scheduler_heartbeat_age_seconds', 'time']);
});

test('queue depth is reported honestly once jobs exist', function (): void {
    DB::table('jobs')->insert([
        ['queue' => 'emails', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => 0, 'created_at' => 0],
        ['queue' => 'emails', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => 0, 'created_at' => 0],
    ]);

    $this->getJson('/v1/health')
        ->assertOk()
        ->assertJsonPath('checks.queue.pending', 2);
});

test('scheduler heartbeat age is reported once a tick has been recorded', function (): void {
    // Freeze the clock so the age is exactly 42s — no flake at second boundaries.
    Carbon::setTestNow('2025-06-01 10:00:00');

    Cache::put('sewa.scheduler.heartbeat', now()->subSeconds(42)->toIso8601String(), 300);

    $this->getJson('/v1/health')
        ->assertOk()
        ->assertJsonPath('scheduler_heartbeat_age_seconds', 42);
});

afterEach(function (): void {
    Carbon::setTestNow();
});
