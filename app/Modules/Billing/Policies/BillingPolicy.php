<?php

namespace App\Modules\Billing\Policies;

use App\Models\User;

/**
 * Billing policy (12-billing-finance §4 + 05-admin-panel §5):
 * finance + admin full; everyone else none at the admin surface
 * (portal visibility is the portal role matrix, not this policy).
 */
class BillingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('billing.view') || $user->hasPermissionTo('billing.manage');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('billing.manage');
    }
}
