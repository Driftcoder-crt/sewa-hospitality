<?php

namespace App\Modules\Portal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Portal\Models\PortalDocument;
use App\Modules\Portal\Models\PortalNotification;
use App\Modules\Portal\Models\PortalThread;
use App\Modules\Portal\Services\TenantAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(TenantAccess $access): View
    {
        $moves = $access->moves()
            ->with(['destinationCity'])
            ->withCount(['checklistItems as pending_count' => fn ($q) => $q->where('status', 'pending')])
            ->orderByRaw("case stage when 'intake' then 0 when 'planning' then 1 when 'in-progress' then 2 when 'settling' then 3 when 'complete' then 4 else 5 end")
            ->limit(4)
            ->get();

        $moveIds = $moves->pluck('id');

        // Next 3 checklist items across the visible moves (due soonest first).
        $nextTasks = DB::table('portal_checklist_items')
            ->whereIn('move_record_id', $moveIds)
            ->where('status', 'pending')
            ->orderByRaw('due_at is null, due_at asc')
            ->limit(3)
            ->get();

        $latestDocuments = PortalDocument::query()
            ->whereIn('move_record_id', $moveIds)
            ->with(['media', 'move'])
            ->latest()
            ->limit(4)
            ->get();

        $unreadThreads = PortalThread::query()
            ->whereIn('move_record_id', $moveIds)
            ->open()
            ->with('move')
            ->whereHas('messages', fn ($q) => $q->whereNull('read_at')->where('sender_role', 'consultant'))
            ->limit(3)
            ->get();

        $notifications = PortalNotification::query()
            ->forUser((string) auth()->id())
            ->latest()
            ->limit(5)
            ->get();

        return view('portal.dashboard', [
            'moves' => $moves,
            'nextTasks' => $nextTasks,
            'latestDocuments' => $latestDocuments,
            'unreadThreads' => $unreadThreads,
            'notifications' => $notifications,
        ]);
    }
}
