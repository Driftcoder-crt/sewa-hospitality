<?php

namespace App\Modules\Services\Policies;

use App\Models\User;
use App\Modules\Services\Models\Service;

/**
 * Services permission matrix (04-modules/02-services-module.md §4):
 * editor edits content; admin publishes + owns lead_tag/coverage/SEO
 * changes (analytics continuity rule §5). super-admin bypasses via
 * Gate::before.
 */
class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'editor']);
    }

    public function view(User $user, Service $service): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'editor']);
    }

    public function update(User $user, Service $service): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'editor']);
    }

    /** Publish + SEO + lead_tag + coverage changes are admin+. */
    public function publish(User $user, Service $service): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }
}
