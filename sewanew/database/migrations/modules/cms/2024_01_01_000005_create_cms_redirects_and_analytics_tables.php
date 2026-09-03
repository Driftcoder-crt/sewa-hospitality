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
        Schema::create('cms_redirects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('from_path')->unique();
            $table->string('to_path');
            $table->integer('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->integer('hit_count')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
            $table->ulid('created_by')->nullable();

            $table->index('is_active');
            $table->index('from_path');
        });

        Schema::create('cms_analytics', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->morphs('trackable'); // page_id, page_type or block_id, block_type
            $table->string('event_type'); // view, click, conversion, etc.
            $table->json('metadata')->nullable();
            $table->string('session_id')->nullable();
            $table->ulid('user_id')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->timestamps();

            $table->index(['trackable_type', 'trackable_id']);
            $table->index('event_type');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_analytics');
        Schema::dropIfExists('cms_redirects');
    }
};
