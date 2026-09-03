<?php

namespace App\Modules\Portal\Livewire;

use App\Modules\Organizations\Models\Organization;
use App\Modules\Portal\Services\InvitationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Invitations manager (04 doc §4.5): invite org users
 * (manager/employee/billing) with the magic-link flow + membership
 * roster per organization.
 */
#[Layout('layouts.admin')]
class InvitationsManager extends Component
{
    public string $orgId = '';

    public string $name = '';

    public string $email = '';

    public string $roleInOrg = 'employee';

    public bool $invited = false;

    public function render(): View
    {
        $this->authorize('viewAny', Organization::class);

        $organizations = Organization::query()->orderBy('name')->get(['id', 'name']);
        $members = collect();

        if ($this->orgId !== '') {
            $organization = Organization::query()->with(['users'])->findOrFail($this->orgId);
            $members = $organization->users->map(fn ($user) => [
                'user' => $user,
                'role' => $user->pivot->role_in_org,
                'joined' => $user->pivot->joined_at,
            ]);
        }

        return view('portal.livewire.invitations-manager', [
            'organizations' => $organizations,
            'members' => $members,
        ]);
    }

    public function invite(): void
    {
        $this->validate([
            'orgId' => ['required', 'exists:organizations,id'],
            'name' => ['nullable', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190'],
            'roleInOrg' => ['required', 'in:manager,employee,billing'],
        ]);

        $organization = Organization::query()->findOrFail($this->orgId);

        $this->authorize('update', $organization);

        app(InvitationService::class)->invite(
            $organization,
            $this->email,
            $this->name,
            $this->roleInOrg,
            auth()->user(),
        );

        $this->invited = true;
        $this->reset(['name', 'email']);

        $this->dispatch('notify', tone: 'success', message: 'Invitation queued — the magic link is on its way (72h validity).');
    }
}
