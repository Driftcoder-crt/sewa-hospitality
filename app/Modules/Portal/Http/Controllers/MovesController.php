<?php

namespace App\Modules\Portal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Portal\Services\TenantAccess;
use Illuminate\Contracts\View\View;

class MovesController extends Controller
{
    public function __construct(private readonly TenantAccess $access) {}

    /** Org-wide board for managers, own moves for employees (04 doc §5). */
    public function index(): View
    {
        $moves = $this->access->moves()
            ->with(['destinationCity', 'employee'])
            ->withCount(['checklistItems as pending_count' => fn ($q) => $q->where('status', 'pending')])
            ->latest('move_date')
            ->paginate(12);

        $stageDistribution = $this->access->moves()
            ->selectRaw('stage, count(*) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage');

        return view('portal.moves.index', [
            'moves' => $moves,
            'stageDistribution' => $stageDistribution,
        ]);
    }

    /** Timeline (stage progress), services, checklist, consultant card. */
    public function show(string $move): View
    {
        $move = $this->access->authorizeMove($move);
        $move->load(['destinationCity', 'employee', 'consultant', 'organization']);

        return view('portal.moves.show', [
            'move' => $move,
            'checklist' => $move->checklistItems()->orderByRaw('due_at is null, due_at asc')->get(),
            'documentsCount' => $move->documents()->count(),
            'openThreads' => $move->threads()->open()->count(),
        ]);
    }
}
