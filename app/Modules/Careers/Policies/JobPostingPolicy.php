<?php

namespace App\Modules\Careers\Policies;

use App\Models\User;
use App\Modules\Careers\Models\JobPosting;

/**
 * Job posting policy (06-hr doc §4 permissions): hr-manager owns the
 * careers module; recruiters work applications, not postings; editors
 * have no place here.
 */
class JobPostingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('careers.view');
    }

    public function view(User $user, JobPosting $posting): bool
    {
        return $user->hasPermissionTo('careers.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('careers.create');
    }

    public function update(User $user, JobPosting $posting): bool
    {
        return $user->hasPermissionTo('careers.update');
    }

    public function delete(User $user, JobPosting $posting): bool
    {
        return $user->hasPermissionTo('careers.delete');
    }
}
