<?php

namespace App\Modules\Cities\Policies;

use App\Models\User;
use App\Modules\Cities\Models\City;

/**
 * Cities permission matrix (04-modules/10-cities-content.md §4):
 * editor+ manage city content; publish/verify are admin+ (matching the
 * services split). super-admin bypasses via Gate::before.
 */
class CityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'editor']);
    }

    public function view(User $user, City $city): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'editor']);
    }

    public function update(User $user, City $city): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'editor']);
    }

    public function publish(User $user, City $city): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function delete(User $user, City $city): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }
}
