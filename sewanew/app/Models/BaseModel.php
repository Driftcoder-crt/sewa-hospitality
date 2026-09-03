<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

abstract class BaseModel extends Model
{
    use HasUuids;

    /**
     * The model's default primary key type.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = $model->generateUlid();
            }
        });
    }

    /**
     * Generate a ULID for the model.
     */
    protected function generateUlid(): string
    {
        return \Illuminate\Support\Str::ulid()->toString();
    }
}
