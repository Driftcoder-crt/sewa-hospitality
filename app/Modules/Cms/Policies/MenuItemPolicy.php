<?php

namespace App\Modules\Cms\Policies;

use App\Models\User;
use App\Modules\Cms\Models\MenuItem;

/**
 * Menu permission matrix (04-modules/01-cms.md §4): `editor`+ manage
 * navigation. super-admin bypasses via Gate::before.
 */
class MenuItemPolicy
{
    private const EDITORS = ['super-admin', 'admin', 'editor'];

    public function updateAny(User $user): bool
    {
        return $user->hasAnyRole(self::EDITORS);
    }

    public function update(User $user, MenuItem $item): bool
    {
        return $user->hasAnyRole(self::EDITORS);
    }

    public function delete(User $user, MenuItem $item): bool
    {
        return $user->hasAnyRole(self::EDITORS);
    }
}
