<?php

namespace App\Modules\Blog\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'blog_tags';

    protected $fillable = [
        'name',
        'slug',
    ];

    public function posts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'blog_post_tag');
    }
}
