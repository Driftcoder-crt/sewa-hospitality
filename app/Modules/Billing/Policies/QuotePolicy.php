<?php

namespace App\Modules\Billing\Policies;

use App\Models\User;

class QuotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('billing.view') || $user->hasPermissionTo('billing.manage');
    }

    public function view(User $user, Quote $quote): bool
    {
        return $user->hasPermissionTo('billing.view') || $user->hasPermissionTo('billing.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('billing.manage');
    }

    public function update(User $user, Quote $quote): bool
    {
        return $user->hasPermissionTo('billing.manage');
    }

    public function delete(User $user, Quote $quote): bool
    {
        // Financial records are never hard-deleted (schema §12).
        return false;
    }
}
