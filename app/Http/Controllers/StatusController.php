<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\HealthController;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * GET /status — the honest public status page (06-hosting-deployment.md §9,
 * 12-monitoring.md §5). Cached 30s; itself a trust asset for corporate
 * clients. NEVER fakes a value: surfaces the page cannot truly observe
 * (scheduler with no tick, queue tables not migrated) render 'unknown'.
 */
final class StatusController
{
    public function __invoke(): View
    {
        $checks = Cache::remember('sewa.status', 30, function (): array {
            $heartbeatAge = HealthController::heartbeatAge();

            $scheduler = match (true) {
                $heartbeatAge === null => 'unknown',
                $heartbeatAge < 90 => 'green',
                $heartbeatAge < 300 => 'amber',
                default => 'red',
            };

            $pending = null;
            $failed = null;

            try {
                $pending = (int) DB::table('jobs')->count();
                $failed = (int) DB::table('failed_jobs')->count();
            } catch (Throwable) {
                // Queue tables not migrated yet — report unknown, never a
                // fabricated zero.
            }

            $queue = match (true) {
                $pending === null || $failed === null => 'unknown',
                $failed > 50 => 'red',
                $failed > 0 => 'amber',
                default => 'green',
            };

            return [
                // Website/API/Portal are served by this very process: if
                // PHP were down, this page could not render at all. 'ok'
                // here is therefore always true — and that is honest.
                'website' => ['status' => 'ok'],
                'api' => ['status' => 'ok'],
                'portal' => ['status' => 'ok'],
                'scheduler' => [
                    'status' => $scheduler,
                    'last_tick_age' => $heartbeatAge,
                ],
                'queue' => [
                    'status' => $queue,
                    'pending' => $pending,
                    'failed' => $failed,
                ],
                'computed_at' => now()->toIso8601String(),
            ];
        });

        return view('status', [
            'checks' => $checks,
            'computed_at' => $checks['computed_at'],
        ]);
    }
}
