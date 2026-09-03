<?php

namespace App\Modules\Portal\Policies;

use App\Models\User;

/** Threads: consultants see assigned-move threads; manage is global. */
class PortalThreadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('portal.view') || $user->hasPermissionTo('portal.manage');
    }

    public function update(User $user, PortalThread $thread): bool
    {
        if ($user->hasPermissionTo('portal.manage')) {
            return true;
        }

        return $user->hasPermissionTo('portal.view')
            && $thread->move?->primary_consultant_user_id === $user->getKey();
    }
}
