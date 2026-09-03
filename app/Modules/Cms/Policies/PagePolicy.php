<?php

namespace App\Modules\Cms\Policies;

use App\Models\User;
use App\Modules\Cms\Models\Page;

/**
 * CMS pages permission matrix (04-modules/01-cms.md §4): `editor`+
 * manage pages and publish. author/authors are blog-surface (M4), not
 * CMS-page roles. super-admin bypasses via Gate::before.
 */
class PagePolicy
{
    private const EDITORS = ['super-admin', 'admin', 'editor'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::EDITORS);
    }

    public function view(User $user, Page $page): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::EDITORS);
    }

    public function update(User $user, Page $page): bool
    {
        return $user->hasAnyRole(self::EDITORS);
    }

    public function delete(User $user, Page $page): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function publish(User $user, Page $page): bool
    {
        return $user->hasAnyRole(self::EDITORS);
    }
}
