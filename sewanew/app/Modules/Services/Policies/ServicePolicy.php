<?php

namespace App\Modules\Services\Policies;

use App\Models\User;
use App\Modules\Services\Models\Service;

class ServicePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Service $service): bool
    {
        return $service->published_at !== null || 
               $user->can('edit', $service) ||
               $user->hasRole(['admin', 'editor']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'editor']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Service $service): bool
    {
        return $user->hasRole(['admin', 'editor']) ||
               ($user->id === $service->created_by && $user->hasRole(['author']));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Service $service): bool
    {
        return $user->hasRole(['admin']);
    }

    /**
     * Determine whether the user can publish the model.
     */
    public function publish(User $user, Service $service): bool
    {
        return $user->hasRole(['admin', 'editor']);
    }

    /**
     * Determine whether the user can feature the model.
     */
    public function feature(User $user, Service $service): bool
    {
        return $user->hasRole(['admin']);
    }
}
