<?php

namespace App\Modules\Billing\Policies;

use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('billing.view') || $user->hasPermissionTo('billing.manage');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('billing.view') || $user->hasPermissionTo('billing.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('billing.manage');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('billing.manage');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return false;
    }
}
