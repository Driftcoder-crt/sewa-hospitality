<?php

namespace App\Modules\Billing\Policies;

use App\Models\User;

class InvoicePaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('billing.view') || $user->hasPermissionTo('billing.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('billing.manage');
    }
}
