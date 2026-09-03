<?php

namespace App\Modules\Careers\Policies;

use App\Models\User;
use App\Modules\Careers\Models\AuthorProfile;
use App\Modules\Careers\Models\Employee;

/** Employees directory + author profiles — hr.* matrix rows. */
class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr.view');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('hr.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr.create');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('hr.update');
    }

    public function updateAuthorProfile(User $user, AuthorProfile $profile): bool
    {
        return $user->hasPermissionTo('hr.update');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('hr.delete');
    }
}
