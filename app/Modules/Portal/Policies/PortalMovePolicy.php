<?php

namespace App\Modules\Portal\Policies;

use App\Models\User;

/**
 * Portal admin policy (04-client-portal §4 + 05-admin-panel §5):
 * admin+ (portal.manage) all; consultant scoped to assigned moves;
 * hr-manager read for coordination. Documents/threads ride the move's
 * policy semantics via their move link.
 */
class PortalMovePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('portal.view') || $user->hasPermissionTo('portal.manage');
    }

    public function view(User $user, PortalMove $move): bool
    {
        if ($user->hasPermissionTo('portal.manage') || $user->hasPermissionTo('hr.view')) {
            return true;
        }

        // Consultants: assigned moves only.
        return $user->hasPermissionTo('portal.view')
            && $move->primary_consultant_user_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('portal.manage');
    }

    public function update(User $user, PortalMove $move): bool
    {
        if ($user->hasPermissionTo('portal.manage')) {
            return true;
        }

        return $user->hasPermissionTo('portal.view')
            && $move->primary_consultant_user_id === $user->getKey();
    }

    public function delete(User $user, PortalMove $move): bool
    {
        // Moves are never deleted (RESTRICT web of financial/legal
        // references) — archive via cancelled status instead.
        return false;
    }
}
