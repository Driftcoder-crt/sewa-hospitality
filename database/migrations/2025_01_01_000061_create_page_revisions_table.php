<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Page revisions (04-modules/01-cms.md §4.7 + §5): every save produces
 * a revision row; the last 20 per page are kept (RevisionManager prunes
 * beyond the cap); restoring an old revision creates a NEW revision —
 * the trail is never destructive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_revisions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('page_id');
            $table->json('snapshot');
            $table->ulid('author_user_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('page_id')
                ->references('id')->on('pages')
                ->cascadeOnDelete();
            $table->index(['page_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_revisions');
    }
};
