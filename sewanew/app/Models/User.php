<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends BaseModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the roles assigned to the user.
     */
    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\Role::class, 'model_has_roles');
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}
