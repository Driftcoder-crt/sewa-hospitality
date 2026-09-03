<?php

namespace App\Modules\Blog\Policies;

use App\Models\User;
use App\Modules\Blog\Models\Post;

class PostPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Post $post): bool
    {
        if ($post->status === 'published') {
            return true;
        }

        return $user && ($user->isAdmin() || $post->author_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('editor');
    }

    public function update(User $user, Post $post): bool
    {
        return $user->hasRole('admin') || $post->author_id === $user->id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->hasRole('admin');
    }

    public function publish(User $user, Post $post): bool
    {
        return $user->hasRole('admin') || $user->hasRole('editor');
    }
}
