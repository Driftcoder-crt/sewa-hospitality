<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

/**
 * Admin dashboard (04-modules/05-admin-panel.md §3): honest KPI tiles,
 * queue health and the audit activity feed. Tiles stay at `null` — the
 * view renders an em dash — until the owning module wires real data.
 * No invented numbers, ever (01-platform-vision/02-brand §1 "Honest").
 */
#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public function render(): View
    {
        return view('admin.dashboard', [
            // Wired in M3 (leads inbox + sla:calculate monitor).
            'leadsToday' => null,
            'leadsSla' => null,
            // Wired in M3 (careers ATS).
            'applications' => null,
            // Wired in M4 (testimonials/reviews engine).
            'reviews' => null,
            // Wired in M5 (portal ops).
            'openMoves' => null,
            // Real data from day one:
            'queue' => $this->queueHealth(),
            'activityFeed' => $this->activityFeed(),
        ]);
    }

    /**
     * Failed + pending job counts (03-technical-specs/07-queues-scheduling.md).
     * Guarded so the dashboard still renders before the queue tables exist.
     *
     * @return array{failed: int, pending: int}|null
     */
    protected function queueHealth(): ?array
    {
        try {
            return [
                'failed' => (int) DB::table('failed_jobs')->count(),
                'pending' => (int) DB::table('jobs')->count(),
            ];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Latest 8 audit rows (activity_log, 03-technical-specs/
     * 03-database-schema.md §11). Guarded until the audit writer lands;
     * actor names resolve in one extra query to avoid lazy-loading.
     */
    protected function activityFeed(): Collection
    {
        if (! class_exists(ActivityLog::class)) {
            return collect();
        }

        try {
            $rows = ActivityLog::query()
                ->latest()
                ->limit(8)
                ->get(['id', 'user_id', 'context', 'action', 'created_at']);

            $actors = User::query()
                ->whereIn('id', $rows->pluck('user_id')->filter()->unique())
                ->pluck('name', 'id');

            return $rows->map(fn (ActivityLog $row): array => [
                'actor' => $actors[$row->user_id] ?? 'System',
                'context' => (string) $row->context,
                'action' => (string) $row->action,
                'at' => $row->created_at?->diffForHumans(),
            ]);
        } catch (Throwable) {
            return collect();
        }
    }
}
