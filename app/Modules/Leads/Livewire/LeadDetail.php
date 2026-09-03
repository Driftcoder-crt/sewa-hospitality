<?php

namespace App\Modules\Leads\Livewire;

use App\Models\User;
use App\Modules\Leads\Enums\LeadEventType;
use App\Modules\Leads\Enums\LeadStatus;
use App\Modules\Leads\Events\LeadStatusChanged;
use App\Modules\Leads\Models\Lead;
use App\Modules\Leads\Services\LeadStatusMachine;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Lead detail (03-leads-crm §4.2): full submission + timeline + status
 * machine + assignment + next action + lost reasons. First status move
 * off "new" stamps first_response_at — the SLA clock's honest stop.
 */
#[Layout('layouts.admin')]
class LeadDetail extends Component
{
    public Lead $lead;

    public string $note = '';

    public string $status = '';

    public string $lostReason = '';

    public string $dealReference = '';

    public string $assignedUserId = '';

    public ?string $nextActionAt = null;

    public function mount(Lead $lead): void
    {
        $this->authorize('view', $lead);
        $this->syncForm();
    }

    public function syncForm(): void
    {
        $this->status = $this->lead->status->value;
        $this->lostReason = (string) $this->lead->lost_reason;
        $this->dealReference = (string) ($this->lead->enrichment['deal_reference'] ?? '');
        $this->assignedUserId = (string) ($this->lead->assigned_user_id ?? '');
        $this->nextActionAt = $this->lead->next_action_at?->format('Y-m-d\TH:i');
    }

    /** Status transition — machine-guarded, logged, evented. */
    public function changeStatus(): void
    {
        $this->authorize('update', $this->lead);

        $from = $this->lead->status;
        $to = LeadStatus::from($this->status);

        try {
            LeadStatusMachine::assertTransition($from, $to, array_merge(
                $this->lead->enrichment ?? [],
                ['deal_reference' => $this->dealReference],
            ));
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        if ($to === LeadStatus::Lost && $this->lostReason === '') {
            throw ValidationException::withMessages(['lost_reason' => 'A lost lead needs a reason (codes keep the pipeline honest).']);
        }

        $enrichment = $this->lead->enrichment ?? [];

        if ($this->dealReference !== '') {
            $enrichment['deal_reference'] = $this->dealReference;
        }

        $this->lead->forceFill([
            'status' => $to,
            'lost_reason' => $to === LeadStatus::Lost ? $this->lostReason : null,
            'enrichment' => $enrichment,
            // First status move off "new" = first response (SLA clock stop).
            'first_response_at' => $this->lead->first_response_at ?? ($from === LeadStatus::New ? now() : $this->lead->first_response_at),
        ])->save();

        $this->lead->logEvent(LeadEventType::Status, ['from' => $from->value, 'to' => $to->value]);
        LeadStatusChanged::dispatch($this->lead, $from, $to);
        ActivityLogger::log('admin', 'update', $this->lead, ['status' => [$from->value => $to->value]]);

        // Livewire persists the error bag across requests: without an
        // explicit reset, a resolved "requires deal reference" error
        // keeps rendering next to the successful transition.
        $this->resetValidation();
        $this->syncForm();
        $this->dispatch('notify', tone: 'success', message: 'Status → '.$to->label());
    }

    public function changeAssignment(): void
    {
        $this->authorize('assign', Lead::class);

        $previous = $this->lead->assigned_user_id;
        $this->lead->forceFill(['assigned_user_id' => $this->assignedUserId !== '' ? $this->assignedUserId : null])->save();
        $this->lead->logEvent(LeadEventType::Assign, [
            'assigned_to' => $this->assignedUserId ?: null,
            'previous' => $previous,
            'strategy' => 'manual',
        ]);
        ActivityLogger::log('admin', 'update', $this->lead, ['assigned_to' => $this->assignedUserId ?: null]);

        $this->dispatch('notify', tone: 'success', message: 'Assignment updated.');
    }

    public function changeNextAction(): void
    {
        $this->authorize('update', $this->lead);

        $this->validate(['nextActionAt' => ['nullable', 'date', 'after:2020-01-01']]);

        $this->lead->forceFill(['next_action_at' => $this->nextActionAt ? Carbon::parse($this->nextActionAt) : null])->save();
        $this->dispatch('notify', tone: 'success', message: 'Next action saved.');
    }

    public function addNote(): void
    {
        $this->authorize('update', $this->lead);
        $this->validate(['note' => ['required', 'string', 'min:2', 'max:2000']]);

        $this->lead->logEvent(LeadEventType::Note, ['note' => $this->note]);
        $this->note = '';
        $this->dispatch('notify', tone: 'success', message: 'Note added to the timeline.');
    }

    public function render(): View
    {
        $this->authorize('view', $this->lead);

        return view('leads.livewire.lead-detail', [
            'events' => $this->lead->events()->with('user:id,name')->orderByDesc('created_at')->limit(100)->get(),
            'statuses' => collect(LeadStatus::pipeline())
                ->mapWithKeys(fn (LeadStatus $s): array => [$s->value => $s->label()]),
            'allowedTargets' => collect(LeadStatusMachine::targets($this->lead->status))
                ->mapWithKeys(fn (LeadStatus $s): array => [$s->value => $s->label()]),
            'lostReasons' => [
                'budget' => 'Budget mismatch',
                'timing' => 'Timing / postponed',
                'competitor' => 'Chose a competitor',
                'no_response' => 'No response',
                'not_a_fit' => 'Not a fit',
                'other' => 'Other',
            ],
            'consultants' => User::query()->role('consultant')->orderBy('name')->get(['id', 'name']),
            'canAssign' => auth()->user()->can('assign', Lead::class),
            'canSeePii' => auth()->user()->can('viewPii', Lead::class),
        ]);
    }
}
