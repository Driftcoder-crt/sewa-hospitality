<?php

namespace App\Modules\Cities\Policies;

use App\Models\User;
use App\Modules\Cities\Models\HousingUnit;

/**
 * Housing inventory permissions (04-modules/10-cities-content.md §4):
 * editor+ manage listings; verification (the Sewa Verified badge) is
 * admin+ — the badge is a trust claim, not a content edit.
 */
class HousingUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'editor']);
    }

    public function view(User $user, HousingUnit $unit): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'editor']);
    }

    public function update(User $user, HousingUnit $unit): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'editor']);
    }

    /** The Sewa Verified action is admin+ (trust claim). */
    public function verify(User $user, HousingUnit $unit): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function delete(User $user, HousingUnit $unit): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }
}
