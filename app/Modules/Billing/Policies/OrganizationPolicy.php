<?php

namespace App\Modules\Billing\Policies;

use App\Models\User;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('billing.view')
            || $user->hasPermissionTo('billing.manage')
            || $user->hasPermissionTo('portal.manage');
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->hasPermissionTo('billing.manage') || $user->hasPermissionTo('portal.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('billing.manage') || $user->hasPermissionTo('portal.manage');
    }
}
