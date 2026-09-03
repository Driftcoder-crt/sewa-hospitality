<?php

namespace App\Modules\Testimonials\Policies;

use App\Models\User;
use App\Modules\Testimonials\Models\Testimonial;

/** Testimonials manager policy (testimonials.* matrix rows). */
class TestimonialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('testimonials.view');
    }

    public function view(User $user, Testimonial $testimonial): bool
    {
        return $user->hasPermissionTo('testimonials.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('testimonials.create');
    }

    public function update(User $user, Testimonial $testimonial): bool
    {
        return $user->hasPermissionTo('testimonials.update');
    }

    public function delete(User $user, Testimonial $testimonial): bool
    {
        return $user->hasPermissionTo('testimonials.delete');
    }
}
