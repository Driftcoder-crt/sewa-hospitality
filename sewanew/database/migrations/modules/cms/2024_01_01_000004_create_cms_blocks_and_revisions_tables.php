<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cms_blocks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('type'); // hero, features, testimonials, cta, etc.
            $table->ulid('page_id')->nullable()->constrained('cms_pages')->onDelete('cascade');
            $table->json('data')->nullable(); // Block content as JSON
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('template')->default('default');
            $table->timestamps();
            $table->ulid('created_by')->nullable();
            $table->ulid('updated_by')->nullable();

            $table->index('page_id');
            $table->index('type');
            $table->index('order');
        });

        Schema::create('cms_revisions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->morphs('revisionable'); // page_id, page_type or block_id, block_type
            $table->ulid('user_id')->nullable();
            $table->string('action'); // created, updated, published, etc.
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('reason')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['revisionable_type', 'revisionable_id']);
            $table->index('user_id');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_revisions');
        Schema::dropIfExists('cms_blocks');
    }
};
