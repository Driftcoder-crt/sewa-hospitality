<?php

namespace App\Modules\Portal\Services;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationUser;
use InvalidArgumentException;

/**
 * Portal tenancy resolver (04-client-portal.md §2): EVERY portal query
 * roots at the signed-in user's organization membership — manager sees
 * org-wide, employee sees own move, billing sees invoices. This service
 * resolves that membership once per request and hands out the guard
 * helpers the controllers/Livewire call instead of hand-rolled where()
 * chains (the isolation matrix has one implementation, tested exhaustively).
 */
class PortalContext
{
    /**
     * Resolved membership for the current request [user, organization, role].
     *
     * @var array{user: User, organization: Organization, role: string}|null
     */
    private ?array $resolved = null;

    public function __construct(private readonly ?User $user = null)
    {
        // The container resolves this with no arguments on every portal
        // request; the authenticated user is read lazily in resolve() —
        // a promoted readonly property cannot be reassigned here.
    }

    /** True when the signed-in user holds a portal Spatie role. */
    public static function exists(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole(['client-manager', 'client-employee']);
    }

    /** Membership role: manager|employee|billing. */
    public function role(): string
    {
        return $this->resolve()['role'];
    }

    public function user(): User
    {
        return $this->resolve()['user'];
    }

    public function organization(): Organization
    {
        return $this->resolve()['organization'];
    }

    /** True when the user may see org-wide surfaces (moves board, invoices). */
    public function isOrgWide(): bool
    {
        return in_array($this->role(), ['manager', 'billing'], true);
    }

    /** @return list<string> the user's organization ids (defensive — normally 1). */
    public function organizationIds(): array
    {
        return OrganizationUser::query()
            ->where('user_id', $this->user()->getKey())
            ->pluck('organization_id')
            ->all();
    }

    /** Org role for a given organization id (null when not a member). */
    public function roleIn(string $organizationId): ?string
    {
        return OrganizationUser::query()
            ->where('user_id', $this->user()->getKey())
            ->where('organization_id', $organizationId)
            ->value('role_in_org');
    }

    /**
     * Resolve the membership, caching it for the request — but never
     * across an identity switch: this object can outlive a request in
     * process-persistent runtimes (feature tests, Octane), and a stale
     * membership there is a tenant-isolation hole, not a performance win.
     *
     * @return array{user: User, organization: Organization, role: string}
     */
    private function resolve(): array
    {
        $user = $this->user ?? auth()->user();

        if ($this->resolved !== null && $this->resolved['user']->getKey() === $user?->getKey()) {
            return $this->resolved;
        }

        if (! $user instanceof User) {
            throw new InvalidArgumentException('PortalContext requires an authenticated user.');
        }

        $membership = OrganizationUser::query()
            ->where('user_id', $user->getKey())
            ->orderBy('created_at')
            ->first();

        if ($membership === null) {
            throw new InvalidArgumentException('User has no portal membership.');
        }

        return $this->resolved = [
            'user' => $user,
            'organization' => Organization::query()->findOrFail($membership->organization_id),
            'role' => (string) $membership->role_in_org,
        ];
    }
}
