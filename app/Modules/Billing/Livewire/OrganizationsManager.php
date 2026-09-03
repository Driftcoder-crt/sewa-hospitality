<?php

namespace App\Modules\Billing\Livewire;

use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\Quote;
use App\Modules\Organizations\Models\Organization;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Organizations manager (12 doc §4.4): billing profile — GSTIN, PAN,
 * address, payment terms + the members roster. One screen, inline edit.
 */
#[Layout('layouts.admin')]
class OrganizationsManager extends Component
{
    public ?string $editingId = null;

    public string $name = '';

    public string $gstin = '';

    public string $pan = '';

    public string $email = '';

    public string $line1 = '';

    public string $city = '';

    public string $state = '';

    public string $postalCode = '';

    public string $notes = '';

    public function edit(string $orgId): void
    {
        $organization = Organization::query()->findOrFail($orgId);
        $this->authorize('update', $organization);

        $this->editingId = $orgId;
        $this->name = (string) $organization->name;
        $this->gstin = (string) ($organization->gstin ?? '');
        $this->pan = (string) ($organization->pan ?? '');
        $this->email = (string) ($organization->billing_address['email'] ?? '');
        $this->line1 = (string) ($organization->billing_address['line1'] ?? '');
        $this->city = (string) ($organization->billing_address['city'] ?? '');
        $this->state = (string) ($organization->billing_address['state'] ?? '');
        $this->postalCode = (string) ($organization->billing_address['postal_code'] ?? '');
        $this->notes = (string) ($organization->notes ?? '');
    }

    public function save(): void
    {
        $organization = Organization::query()->findOrFail($this->editingId);
        $this->authorize('update', $organization);

        $this->validate([
            'name' => ['required', 'string', 'max:190'],
            'gstin' => ['nullable', 'string', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'pan' => ['nullable', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'email' => ['nullable', 'email'],
            'city' => ['nullable', 'string', 'max:120'],
        ], [], [
            'gstin' => 'GSTIN',
            'pan' => 'PAN',
        ]);

        $address = array_filter([
            'line1' => $this->line1 ?: null,
            'city' => $this->city ?: null,
            'state' => $this->state ?: null,
            'postal_code' => $this->postalCode ?: null,
            'email' => $this->email ?: null,
        ]);

        $organization->forceFill([
            'name' => $this->name,
            'gstin' => strtoupper($this->gstin) ?: null,
            'pan' => strtoupper($this->pan) ?: null,
            'billing_address' => $address !== [] ? $address : null,
            'notes' => $this->notes ?: null,
        ])->save();

        ActivityLogger::log('admin', 'update', $organization, ['section' => 'billing_profile']);

        $this->editingId = null;
        $this->dispatch('notify', tone: 'success', message: 'Billing profile saved.');
    }

    public function render(): View
    {
        $this->authorize('viewAny', Organization::class);

        $organizations = Organization::query()
            ->withCount(['users', 'invoices'])
            ->orderBy('name')
            ->get();

        return view('billing.livewire.organizations-manager', [
            'organizations' => $organizations,
            'outstanding' => Invoice::query()->outstanding()
                ->selectRaw('organization_id, sum(total) as due')
                ->groupBy('organization_id')
                ->pluck('due', 'organization_id'),
            'winRate' => $this->quoteWinRate(),
        ]);
    }

    /** Quote win rate (12 doc §4.5 — ties to CRM statuses). */
    private function quoteWinRate(): ?string
    {
        $sent = Quote::query()->whereIn('status', ['sent', 'accepted', 'rejected', 'expired'])->count();
        $accepted = Quote::query()->where('status', 'accepted')->count();

        return $sent === 0 ? null : sprintf('%d%%', (int) round($accepted / $sent * 100));
    }
}
