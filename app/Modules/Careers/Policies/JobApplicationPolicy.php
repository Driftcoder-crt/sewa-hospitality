<?php

namespace App\Modules\Careers\Policies;

use App\Models\User;
use App\Modules\Careers\Models\JobApplication;

/**
 * Application (ATS) policy: recruiter pipeline rights, PII gate on
 * contact details/resume, export audited (06-hr §4.2).
 */
class JobApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('careers.view');
    }

    public function view(User $user, JobApplication $application): bool
    {
        return $user->hasPermissionTo('careers.view');
    }

    /** Contact fields + resume preview. */
    public function viewPii(User $user): bool
    {
        return $user->hasPermissionTo('careers.pii.view');
    }

    public function update(User $user, JobApplication $application): bool
    {
        return $user->hasPermissionTo('careers.update');
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('careers.export');
    }

    public function delete(User $user, JobApplication $application): bool
    {
        return $user->hasPermissionTo('careers.delete');
    }
}
