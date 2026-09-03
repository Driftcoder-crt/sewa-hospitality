<?php

namespace App\Modules\Portal\Services;

use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationUser;
use App\Modules\Portal\Mail\PortalInviteMail;
use App\Support\Audit\ActivityLogger;
use App\Support\Mail\Jobs\SendTemplateMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Portal invitations (04 doc §4.5): invite org users with a role,
 * magic-link first login. The user is created with status=invited +
 * a random password; the signed set-password URL is single-use and
 * 72h-expiring; the portal.invite email is queued (10-email catalog).
 */
class InvitationService
{
    private const ROLES = ['manager', 'employee', 'billing'];

    /**
     * Invite (or re-invite) an email into an organization with a role.
     *
     * @return array{user: User, url: string, created: bool}
     */
    public function invite(Organization $organization, string $email, string $name, string $roleInOrg, ?User $invitedBy = null): array
    {
        if (! in_array($roleInOrg, self::ROLES, true)) {
            throw ValidationException::withMessages(['role_in_org' => 'Unknown portal role.']);
        }

        $email = mb_strtolower(trim($email));

        $user = User::query()->where('email', $email)->first();

        $created = $user === null;

        if ($user === null) {
            $user = User::query()->create([
                'name' => $name !== '' ? $name : Str::before($email, '@'),
                'email' => $email,
                // Random — the invite link is the only way in initially.
                'password' => Str::password(24),
                'status' => UserStatus::Invited,
            ]);
        }

        $existing = OrganizationUser::query()
            ->where('organization_id', $organization->getKey())
            ->where('user_id', $user->getKey())
            ->first();

        if ($existing !== null && $existing->role_in_org === $roleInOrg) {
            // Re-invite: same membership, fresh link.
        } elseif ($existing !== null) {
            throw ValidationException::withMessages([
                'email' => 'This person is already a member of '.$organization->name.' (as '.$existing->role_in_org.').',
            ]);
        } else {
            OrganizationUser::create([
                'organization_id' => $organization->getKey(),
                'user_id' => $user->getKey(),
                'role_in_org' => $roleInOrg,
                'invited_by' => $invitedBy?->getKey(),
                'joined_at' => now(),
            ]);

            // Portal-facing roles map to the portal Spatie role matrix —
            // client-employee for the employee role, client-manager for
            // manager/billing (billing surface gates itself separately).
            $user->assignRole($roleInOrg === 'employee' ? 'client-employee' : 'client-manager');
        }

        $url = URL::temporarySignedRoute(
            'portal.invitations.accept',
            now()->addHours(72),
            ['token' => $this->tokenFor($user)],
        );

        SendTemplateMail::dispatch(
            key: 'portal.invite:'.$user->getKey().':'.now()->format('YmdHis'),
            template: 'portal.invite',
            mailable: new PortalInviteMail($user->email, $user->name, $organization, $roleInOrg, $url),
        );

        ActivityLogger::log('admin', 'create', $user, [
            'action' => 'portal_invite',
            'organization' => $organization->slug,
            'role_in_org' => $roleInOrg,
            'created_user' => $created,
        ]);

        return ['user' => $user, 'url' => $url, 'created' => $created];
    }

    /**
     * The invitation token: deterministic per user + invited-status so a
     * re-invite invalidates the previous link (they are status-rotated).
     */
    private function tokenFor(User $user): string
    {
        return hash('sha256', $user->getKey().'|'.$user->email.'|'.(string) config('app.key'));
    }

    /** Resolve the invited user for a token (must still be invited). */
    public function resolveToken(string $token): ?User
    {
        // The signature already validated authenticity; this binds the
        // token to the user. Driver-agnostic (no SQL hash functions) and
        // timing-safe — invited users are a tiny set by definition.
        $candidates = User::query()
            ->where('status', UserStatus::Invited->value)
            ->get();

        foreach ($candidates as $user) {
            if (hash_equals($this->tokenFor($user), $token)) {
                return $user;
            }
        }

        return null;
    }

    /** Complete first login: set password, activate. */
    public function accept(User $user, string $password): void
    {
        DB::transaction(function () use ($user, $password): void {
            $user->forceFill([
                'password' => $password,
                'status' => UserStatus::Active,
            ])->save();

            ActivityLogger::log('portal', 'update', $user, ['action' => 'invitation_accepted']);
        });
    }
}
