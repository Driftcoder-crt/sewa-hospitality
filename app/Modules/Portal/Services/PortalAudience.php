<?php

namespace App\Modules\Portal\Services;

use App\Models\User;
use App\Modules\Organizations\Models\OrganizationUser;
use App\Modules\Portal\Models\PortalMove;

/**
 * Recipient resolution for portal events (04 doc §7 + 10-email §4):
 * who receives emails and notifications for a move. Managers + billing
 * get org-wide events; the employee gets their own. One implementation
 * — listeners never hand-roll recipient lists.
 */
class PortalAudience
{
    /** Org users with org-wide visibility (managers + billing). */
    public static function orgWideUserIds(string $organizationId): array
    {
        return OrganizationUser::query()
            ->where('organization_id', $organizationId)
            ->whereIn('role_in_org', ['manager', 'billing'])
            ->pluck('user_id')
            ->all();
    }

    /**
     * Invoice/quote email recipients for an organization: users holding
     * the billing role first, managers as fallback — never a bare config
     * address (the doc's "portal managers see own-org invoices" rule).
     *
     * @return list<array{email: string, name: string}>
     */
    public static function billingRecipients(string $organizationId): array
    {
        $recipients = [];

        OrganizationUser::query()
            ->where('organization_id', $organizationId)
            ->whereIn('role_in_org', ['billing', 'manager'])
            ->orderByRaw("case when role_in_org = 'billing' then 0 else 1 end")
            ->get()
            ->each(function (OrganizationUser $membership) use (&$recipients): void {
                $user = User::find($membership->user_id);

                if ($user !== null && ! isset($recipients[$user->email])) {
                    $recipients[$user->email] = ['email' => $user->email, 'name' => $user->name];
                }
            });

        return array_values($recipients);
    }

    /**
     * Move-stage email recipients: the employee (if any) + org-wide
     * users — deduplicated.
     *
     * @return list<array{user: User|null, email: string, name: string}>
     */
    public static function moveRecipients(PortalMove $move): array
    {
        $recipients = [];

        if ($move->employee !== null) {
            $recipients[$move->employee->email] = [
                'user' => $move->employee,
                'email' => $move->employee->email,
                'name' => $move->employee->name,
            ];
        }

        foreach (self::orgWideUserIds((string) $move->organization_id) as $userId) {
            $user = User::find($userId);

            if ($user !== null && ! isset($recipients[$user->email])) {
                $recipients[$user->email] = [
                    'user' => $user,
                    'email' => $user->email,
                    'name' => $user->name,
                ];
            }
        }

        return array_values($recipients);
    }

    /** "What's next" copy per stage (honest, no invented promises). */
    public static function whatsNext(string $stage): string
    {
        return match ($stage) {
            'planning' => 'Your consultant is mapping services, timelines and the first checklist. Expect a planning call shortly.',
            'in-progress' => 'Services are underway — watch your checklist for what we need from you, and your documents tab for paperwork.',
            'settling' => 'You are nearly there: registrations, utilities and settling-in support are in motion.',
            'complete' => 'The move is complete. We would love to hear how it went — a short review helps the next family.',
            'closed' => 'This move is now closed. Your documents and invoices remain available in the portal.',
            default => 'Your consultant team has taken over the intake — you will hear from us with the plan.',
        };
    }
}
