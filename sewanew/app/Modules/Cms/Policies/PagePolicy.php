<?php

declare(strict_types=1);

namespace Modules\Cms\Policies;

use App\Models\User;
use Modules\Cms\Models\Page;

/**
 * Page Policy
 */
class PagePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'editor', 'author']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Page $page): bool
    {
        // Published pages are visible to everyone
        if ($page->isPublished()) {
            return true;
        }

        // Draft/archived pages require authorization
        return $user->hasAnyRole(['admin', 'editor']) 
            || $page->created_by === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'editor', 'author']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Page $page): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('editor')) {
            return true;
        }

        // Authors can only edit their own pages
        if ($user->hasRole('author')) {
            return $page->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Page $page): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('editor')) {
            return !$page->isPublished(); // Editors can't delete published pages
        }

        // Authors can only delete their own unpublished pages
        if ($user->hasRole('author')) {
            return $page->created_by === $user->id && !$page->isPublished();
        }

        return false;
    }

    /**
     * Determine whether the user can publish the model.
     */
    public function publish(User $user, Page $page): bool
    {
        return $user->hasAnyRole(['admin', 'editor']);
    }

    /**
     * Determine whether the user can unpublish the model.
     */
    public function unpublish(User $user, Page $page): bool
    {
        return $user->hasAnyRole(['admin', 'editor']);
    }

    /**
     * Determine whether the user can archive the model.
     */
    public function archive(User $user, Page $page): bool
    {
        return $user->hasAnyRole(['admin', 'editor']);
    }

    /**
     * Determine whether the user can duplicate the model.
     */
    public function duplicate(User $user, Page $page): bool
    {
        return $user->hasAnyRole(['admin', 'editor', 'author']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Page $page): bool
    {
        return $user->hasAnyRole(['admin', 'editor']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Page $page): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can rollback to a revision.
     */
    public function rollback(User $user, Page $page): bool
    {
        return $user->hasAnyRole(['admin', 'editor']);
    }
}
