<?php

namespace App\Modules\Leads\Policies;

use App\Models\User;
use App\Modules\Leads\Models\Lead;

/**
 * Lead policy (04-modules/05-admin-panel.md §5 + 03-leads-crm §4):
 * admin+ (leads.view) sees the inbox; consultants see the pipeline but
 * PII (email/phone) is a separate permission — the matrix keeps them
 * able to work assigned leads while pii.view stays a deliberate grant.
 * Finance sees won-value only (no PII) — enforced in components.
 */
class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('leads.view');
    }

    public function view(User $user, Lead $lead): bool
    {
        if (! $user->hasPermissionTo('leads.view')) {
            return false;
        }

        // Consultants without the assign permission are scoped to their
        // own leads (assignment-only view).
        if ($user->hasPermissionTo('leads.assign')) {
            return true;
        }

        return $lead->assigned_user_id === $user->getKey();
    }

    /** Contact-detail visibility — the PII gate. */
    public function viewPii(User $user): bool
    {
        return $user->hasPermissionTo('leads.pii.view');
    }

    public function update(User $user, Lead $lead): bool
    {
        if (! $user->hasPermissionTo('leads.update')) {
            return false;
        }

        if ($user->hasPermissionTo('leads.assign')) {
            return true;
        }

        return $lead->assigned_user_id === $user->getKey();
    }

    public function assign(User $user): bool
    {
        return $user->hasPermissionTo('leads.assign');
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('leads.export');
    }
}
