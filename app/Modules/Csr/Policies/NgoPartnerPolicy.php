<?php

namespace App\Modules\Csr\Policies;

use App\Models\User;
use App\Modules\Csr\Models\CsrStory;
use App\Modules\Csr\Models\NgoPartner;

/** CSR policy (csr.* matrix rows) — partners and stories share it. */
class NgoPartnerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('csr.view');
    }

    public function view(User $user, NgoPartner|CsrStory $subject): bool
    {
        return $user->hasPermissionTo('csr.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('csr.create');
    }

    public function update(User $user, NgoPartner|CsrStory $subject): bool
    {
        return $user->hasPermissionTo('csr.update');
    }

    public function delete(User $user, NgoPartner|CsrStory $subject): bool
    {
        return $user->hasPermissionTo('csr.delete');
    }
}
