<?php

namespace App\Modules\Cms\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Settings (03-technical-specs/03-database-schema.md §2) — key/value,
 * site-scope, group-owned. The single source of organization identity,
 * NAP, socials, offices, membership badges and integration ids.
 * Read-through caching via SettingsRepository; writes flush + broadcast
 * SettingsUpdated (04-modules/00-module-system.md event catalog).
 */
class Setting extends Model
{
    use HasUlids;

    protected $fillable = ['key', 'value', 'group', 'editable_by_role'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }
}
