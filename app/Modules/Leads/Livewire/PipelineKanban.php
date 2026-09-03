<?php

namespace App\Modules\Leads\Livewire;

use App\Modules\Leads\Enums\LeadEventType;
use App\Modules\Leads\Enums\LeadStatus;
use App\Modules\Leads\Events\LeadStatusChanged;
use App\Modules\Leads\Models\Lead;
use App\Modules\Leads\Services\LeadStatusMachine;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Pipeline kanban (03-leads-crm §4.3): new → contacted → qualified →
 * proposal → won/lost/nurture. Drag (pointer) AND a keyboard-select
 * fallback per card (a11y contract — dragging is never the only path).
 * Won requires a deal reference; every move is logged + evented.
 */
#[Layout('layouts.admin')]
class PipelineKanban extends Component
{
    public function moveLead(string $leadId, string $status): void
    {
        $lead = Lead::query()->findOrFail($leadId);
        $this->authorize('update', $lead);

        $from = $lead->status;
        $to = LeadStatus::from($status);

        if ($from === $to) {
            return;
        }

        try {
            LeadStatusMachine::assertTransition($from, $to, $lead->enrichment ?? []);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['pipeline' => $e->getMessage()]);
        }

        $lead->forceFill([
            'status' => $to,
            'first_response_at' => $lead->first_response_at ?? ($from === LeadStatus::New ? now() : $lead->first_response_at),
        ])->save();

        $lead->logEvent(LeadEventType::Status, ['from' => $from->value, 'to' => $to->value, 'via' => 'kanban']);
        LeadStatusChanged::dispatch($lead, $from, $to);
        ActivityLogger::log('admin', 'update', $lead, ['status' => [$from->value => $to->value], 'via' => 'kanban']);

        $this->dispatch('notify', tone: 'success', message: 'Lead moved to '.$to->label().'.');
    }

    public function render(): View
    {
        $this->authorize('viewAny', Lead::class);

        $user = auth()->user();

        $query = Lead::query()->active()
            ->with(['service:id,name', 'city:id,name', 'assignedTo:id,name'])
            ->when(! $user->hasPermissionTo('leads.assign'), fn ($q) => $q->assignedTo((string) $user->getKey()))
            ->orderByDesc('score')
            ->orderByDesc('created_at');

        $columns = collect(LeadStatus::pipeline())
            ->map(fn (LeadStatus $status): array => [
                'status' => $status,
                'leads' => (clone $query)->where('status', $status)->limit(25)->get(),
            ]);

        return view('leads.livewire.pipeline', [
            'columns' => $columns,
        ]);
    }
}
