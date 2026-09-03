<?php

namespace App\Modules\Leads\Livewire;

use App\Models\User;
use App\Modules\Leads\Enums\LeadEventType;
use App\Modules\Leads\Enums\LeadSource;
use App\Modules\Leads\Enums\LeadStatus;
use App\Modules\Leads\Models\Lead;
use App\Modules\Services\Models\Service;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * CRM inbox (04-modules/03-leads-crm.md §4.1): realtime island with
 * wire:poll, SLA countdown chips (amber ≤25% window, red breached),
 * filters, bulk assign/archive, new-lead toast. Consultants see only
 * their assigned leads; admins see everything.
 */
#[Layout('layouts.admin')]
class LeadsInbox extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    #[Url]
    public string $source = '';

    #[Url]
    public string $serviceId = '';

    #[Url]
    public string $q = '';

    #[Url]
    public bool $showArchived = false;

    /** @var array<int, string> */
    public array $selected = [];

    /** New-lead toast anchor: latest lead id seen on this session. */
    public ?string $lastSeenLeadId = null;

    public function mount(): void
    {
        $this->lastSeenLeadId = session('sewa.crm.last_seen_lead');
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    /** Bulk assign (admin/leads.assign only). */
    public function bulkAssign(string $userId): void
    {
        $this->authorize('assign', Lead::class);

        $leads = Lead::query()->whereIn('id', $this->selected)->active()->get();

        foreach ($leads as $lead) {
            if ($lead->assigned_user_id === $userId) {
                continue;
            }

            $lead->forceFill(['assigned_user_id' => $userId])->save();
            $lead->logEvent(LeadEventType::Assign, ['assigned_to' => $userId, 'strategy' => 'bulk']);
            ActivityLogger::log('admin', 'update', $lead, ['assigned_to' => $userId, 'via' => 'bulk']);
        }

        $this->selected = [];
        $this->dispatch('notify', tone: 'success', message: $leads->count().' lead(s) reassigned.');
    }

    /** Bulk archive — review pile, never data loss (activity-logged). */
    public function bulkArchive(): void
    {
        $this->authorize('assign', Lead::class);

        $count = Lead::query()->whereIn('id', $this->selected)->active()->update(['archived_at' => now()]);
        ActivityLogger::log('admin', 'update', null, ['archived_leads' => $count, 'via' => 'bulk']);

        $this->selected = [];
        $this->dispatch('notify', tone: 'success', message: $count.' lead(s) archived.');
    }

    public function unarchive(string $id): void
    {
        $this->authorize('assign', Lead::class);

        Lead::query()->whereKey($id)->update(['archived_at' => null]);
        $this->dispatch('notify', tone: 'success', message: 'Lead restored to the inbox.');
    }

    /** Role-gated CSV export (03-leads-crm §4.6) — audited. */
    public function exportCsv()
    {
        $this->authorize('export', Lead::class);

        ActivityLogger::log('admin', 'export', null, ['subject' => 'leads', 'filters' => [
            'status' => $this->status, 'source' => $this->source, 'service' => $this->serviceId, 'q' => $this->q,
        ]]);

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['id', 'created_at', 'source', 'type', 'status', 'name', 'email', 'phone', 'company', 'service', 'city', 'locale', 'score', 'sla_due_at', 'first_response_at', 'assigned_to']);

            $this->baseQuery()
                ->with(['service:id,name', 'city:id,name', 'assignedTo:id,email'])
                ->orderByDesc('created_at')
                ->chunk(200, function ($leads) use ($out): void {
                    foreach ($leads as $lead) {
                        fputcsv($out, [
                            $lead->getKey(), $lead->created_at->toIso8601String(), $lead->source->value,
                            $lead->type->value, $lead->status->value, $lead->name, $lead->email,
                            $lead->phone, $lead->company, $lead->service?->name, $lead->city?->name,
                            $lead->locale, $lead->score, $lead->sla_due_at?->toIso8601String(),
                            $lead->first_response_at?->toIso8601String(), $lead->assignedTo?->email,
                        ]);
                    }
                });

            fclose($out);
        }, 'sewa-leads-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function render(): View
    {
        $leads = $this->baseQuery()->orderByDesc('created_at')->paginate(20);

        // New-lead toast (realtime-lite): track the newest row across
        // polls and fire the toast island once per new lead.
        $newest = Lead::query()->active()->latest('created_at')->first();
        if ($newest !== null && $newest->getKey() !== $this->lastSeenLeadId) {
            if ($this->lastSeenLeadId !== null) {
                $this->dispatch('notify', tone: 'info', message: 'New lead: '.str($newest->name)->limit(40));
            }
            $this->lastSeenLeadId = $newest->getKey();
            session(['sewa.crm.last_seen_lead' => $this->lastSeenLeadId]);
        }

        return view('leads.livewire.inbox', [
            'leads' => $leads,
            'statuses' => LeadStatus::options(),
            'sources' => LeadSource::options(),
            'services' => Service::query()->published()->orderBy('name')->get(['id', 'name']),
            'consultants' => User::query()->role('consultant')->orderBy('name')->get(['id', 'name']),
            'canAssign' => auth()->user()->can('assign', Lead::class),
            'canExport' => auth()->user()->can('export', Lead::class),
            'canSeePii' => auth()->user()->can('viewPii', Lead::class),
            'newCount' => Lead::query()->active()->where('status', LeadStatus::New)->count(),
        ]);
    }

    /** The filtered query, shared by render + export (role-scoped). */
    private function baseQuery(): Builder
    {
        $user = auth()->user();

        $query = Lead::query()
            ->with(['service:id,name', 'city:id,name', 'assignedTo:id,name'])
            // Consultants without assignment rights: own leads only.
            ->when(! $user->hasPermissionTo('leads.assign'), fn (Builder $q) => $q->assignedTo((string) $user->getKey()));

        if ($this->showArchived) {
            $query->archived();
        } else {
            $query->active();
        }

        return $query
            ->when($this->status !== '', fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->source !== '', fn (Builder $q) => $q->where('source', $this->source))
            ->when($this->serviceId !== '', fn (Builder $q) => $q->where('service_id', $this->serviceId))
            ->when($this->q !== '', fn (Builder $q) => $q->where(function (Builder $q): void {
                $term = '%'.$this->q.'%';
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('company', 'like', $term);
            }));
    }
}
