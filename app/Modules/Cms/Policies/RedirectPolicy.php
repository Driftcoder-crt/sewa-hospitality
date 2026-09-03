<?php

namespace App\Modules\Cms\Policies;

use App\Models\User;
use App\Modules\Cms\Models\Redirect;

/**
 * Redirect permission matrix (04-modules/01-cms.md §4): redirects are
 * `admin`+ (they shape SEO equity and cache behavior). super-admin
 * bypasses via Gate::before.
 */
class RedirectPolicy
{
    private const ADMINS = ['super-admin', 'admin'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::ADMINS);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::ADMINS);
    }

    public function update(User $user, Redirect $redirect): bool
    {
        return $user->hasAnyRole(self::ADMINS);
    }

    public function delete(User $user, Redirect $redirect): bool
    {
        return $user->hasAnyRole(self::ADMINS);
    }
}
