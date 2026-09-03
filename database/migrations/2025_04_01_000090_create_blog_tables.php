<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blog/News editorial core (03-database-schema §4 + 04-modules/
 * 07-blog-news.md §2): posts are blog|news on one machine; categories
 * are nested; tags are PER-POST only (no sitewide cloud — the
 * reference defect). author_user_id is NOT NULL — the "admin author"
 * is structurally impossible.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- posts ----------------------------------------------------
        Schema::create('posts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug', 190);
            $table->string('type', 10)->index(); // blog|news
            $table->string('title', 190);
            $table->text('excerpt');
            $table->longText('body');
            $table->unsignedBigInteger('cover_media_id')->nullable();
            $table->string('status', 12)->default('draft')->index(); // draft|review|scheduled|published
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('scheduled_at')->nullable();
            $table->ulid('author_user_id'); // NOT NULL — human authorship rule
            $table->ulid('approved_by_user_id')->nullable();
            $table->text('review_notes')->nullable();
            $table->ulid('locale_source_id')->nullable();
            $table->string('canonical', 300)->nullable();
            $table->string('meta_title', 190)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->boolean('noindex')->default(false);
            $table->string('locale', 8)->default('en');
            $table->unsignedSmallInteger('reading_time')->default(1);
            $table->unsignedInteger('word_count')->default(0);
            $table->ulid('created_by')->nullable();
            $table->ulid('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('cover_media_id')->references('id')->on('media')->nullOnDelete();
            $table->foreign('author_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('approved_by_user_id')->references('id')->on('users')->nullOnDelete();

            // Slug unique per type+locale (07 doc §2).
            $table->unique(['type', 'locale', 'slug']);
            $table->index(['status', 'type', 'published_at']);
            $table->index(['locale', 'locale_source_id']);
        });

        // ---- categories (nested) --------------------------------------
        Schema::create('categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug', 190)->unique();
            $table->string('name', 120);
            $table->ulid('parent_id')->nullable();
            $table->text('description')->nullable(); // editable, indexable archive intro
            $table->string('meta_title', 190)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('locale', 8)->default('en');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();
        });

        Schema::create('category_post', function (Blueprint $table) {
            $table->ulid('category_id');
            $table->ulid('post_id');
            $table->primary(['category_id', 'post_id']);

            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
        });

        // ---- tags (per-post only) --------------------------------------
        Schema::create('tags', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug', 190)->unique();
            $table->string('name', 120);
            $table->timestamps();
        });

        Schema::create('tag_post', function (Blueprint $table) {
            $table->ulid('tag_id');
            $table->ulid('post_id');
            $table->primary(['tag_id', 'post_id']);

            $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tag_post');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('category_post');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('posts');
    }
};
