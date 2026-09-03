<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->longText('content')->nullable();
            $table->string('template')->default('default');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->ulid('author_id')->nullable();
            $table->json('seo_data')->nullable();
            $table->json('schema_markup')->nullable();
            $table->string('canonical_url')->nullable();
            $table->boolean('no_index')->default(false);
            $table->boolean('no_follow')->default(false);
            $table->string('og_image')->nullable();
            $table->string('og_title')->nullable();
            $table->string('og_description')->nullable();
            $table->string('twitter_card')->default('summary_large_image');
            $table->string('status')->default('draft'); // draft, published, archived
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->ulid('created_by')->nullable();
            $table->ulid('updated_by')->nullable();
            $table->softDeletes();

            $table->index('slug');
            $table->index('status');
            $table->index('is_published');
            $table->index('published_at');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_pages');
    }
};
