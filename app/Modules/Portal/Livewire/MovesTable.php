<?php

namespace App\Modules\Portal\Livewire;

use App\Modules\Organizations\Models\Organization;
use App\Modules\Portal\Enums\MoveStage;
use App\Modules\Portal\Enums\MoveStatus;
use App\Modules\Portal\Models\PortalMove;
use App\Modules\Portal\Services\MoveReferenceGenerator;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Moves admin (04-client-portal §4.1): filterable list (org, stage,
 * city, consultant) + draft creation. The editor (stage machine,
 * checklist, documents, templates) lives on admin.moves.edit.
 */
#[Layout('layouts.admin')]
class MovesTable extends Component
{
    use WithPagination;

    #[Url]
    public string $stage = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $org = '';

    #[Url]
    public string $q = '';

    public string $creating = '';

    public string $createOrg = '';

    public string $createOrigin = '';

    public string $createDestination = '';

    public string $createMoveDate = '';

    public function updatedCreating(): void
    {
        $this->resetErrorBag('creating');
    }

    public function render(): View
    {
        $this->authorize('viewAny', PortalMove::class);

        $moves = PortalMove::query()
            ->with(['organization', 'destinationCity', 'consultant', 'employee'])
            ->when($this->stage !== '', fn ($q) => $q->where('stage', $this->stage))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->org !== '', fn ($q) => $q->where('organization_id', $this->org))
            ->when($this->q !== '', function ($q) {
                $term = '%'.$this->q.'%';
                $q->where(fn ($inner) => $inner
                    ->where('reference', 'like', $term)
                    ->where('assignee_name', 'like', $term)
                    ->orWhere('origin_city', 'like', $term)
                    ->orWhereHas('employee', fn ($e) => $e->where('name', 'like', $term)->orWhere('email', 'like', $term)));
            })
            // Consultants without manage see only their moves.
            ->when(! auth()->user()->hasPermissionTo('portal.manage'), fn ($q) => $q->assignedTo((string) auth()->id()))
            ->latest('created_at')
            ->paginate(15);

        return view('portal.livewire.moves-table', [
            'moves' => $moves,
            'organizations' => Organization::query()->orderBy('name')->get(['id', 'name']),
            'stages' => MoveStage::options(),
            'statuses' => MoveStatus::options(),
        ]);
    }

    /** Draft move → straight into the editor (04 doc §4.1). */
    public function createDraft(): void
    {
        $this->authorize('create', PortalMove::class);

        $this->validate([
            'creating' => ['required', 'string', 'min:2', 'max:190'],
            'createOrg' => ['required', 'exists:organizations,id'],
            'createOrigin' => ['nullable', 'string', 'max:120'],
            'createDestination' => ['nullable', 'exists:cities,id'],
            'createMoveDate' => ['nullable', 'date'],
        ], [], [
            'creating' => 'assignee name',
        ]);

        // Allocation + insert in ONE transaction (12-billing-finance §2):
        // SequentialNumbering's row lock must survive until the move row
        // exists, or two concurrent drafts can draw the same reference and
        // the UNIQUE index turns the loser into a failed create.
        $move = DB::transaction(function (): PortalMove {
            return PortalMove::query()->create([
                'reference' => app(MoveReferenceGenerator::class)->next(),
                'organization_id' => $this->createOrg,
                'assignee_name' => $this->creating,
                'origin_city' => $this->createOrigin ?: null,
                'destination_city_id' => $this->createDestination ?: null,
                'move_date' => $this->createMoveDate ?: null,
                'stage' => MoveStage::Intake,
                'status' => MoveStatus::Active,
                'service_ids' => [],
                'timeline' => [],
            ]);
        });

        ActivityLogger::log('admin', 'create', $move, ['reference' => $move->reference]);

        $this->reset(['creating', 'createOrigin', 'createDestination', 'createMoveDate']);

        $this->redirectRoute('admin.moves.edit', $move, navigate: true);
    }
}
