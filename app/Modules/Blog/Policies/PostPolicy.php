<?php

namespace App\Modules\Blog\Policies;

use App\Models\User;
use App\Modules\Blog\Models\Post;

/**
 * Editorial policy (07-blog-news §4 permissions): authors own their
 * drafts and can submit for review; editors approve/publish everything;
 * taxonomy is editor+. The four-eyes rule itself lives in
 * PostPublishGate::approve (author ≠ approver, enforced + tested).
 */
class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['blog.view', 'blog.create']);
    }

    public function view(User $user, Post $post): bool
    {
        if ($user->hasPermissionTo('blog.view')) {
            return true;
        }

        // Authors see their own posts only.
        return $post->author_user_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('blog.create');
    }

    public function update(User $user, Post $post): bool
    {
        if ($user->hasPermissionTo('blog.update')) {
            return true;
        }

        // Own drafts/review posts are editable by their author.
        return $post->author_user_id === $user->getKey()
            && in_array($post->status->value, ['draft', 'review'], true);
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('blog.delete');
    }

    /** Approve/reject in the review workflow (four-eyes beyond the gate). */
    public function review(User $user): bool
    {
        return $user->hasPermissionTo('blog.publish');
    }

    public function publish(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('blog.publish');
    }
}
