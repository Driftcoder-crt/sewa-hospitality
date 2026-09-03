<?php

namespace App\Modules\Portal\Policies;

use App\Models\User;

/** Documents: upload/publish under portal.manage; consultants on their moves. */
class PortalDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('portal.view') || $user->hasPermissionTo('portal.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('portal.manage');
    }

    public function update(User $user, PortalDocument $document): bool
    {
        if ($user->hasPermissionTo('portal.manage')) {
            return true;
        }

        return $user->hasPermissionTo('portal.view')
            && $document->move?->primary_consultant_user_id === $user->getKey();
    }
}
