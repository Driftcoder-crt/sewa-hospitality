<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Search query log (03-technical-specs/08-search.md §3): anonymous
 * term + count + locale + zero-results flag → zero-result queries feed
 * the city/content backlog as editorial tickets.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_queries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('term', 200);
            $table->string('locale', 5)->default('en');
            $table->unsignedBigInteger('hits')->default(0);
            $table->unsignedInteger('count')->default(1);
            $table->boolean('zero_results')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['term', 'locale']);
            $table->index(['zero_results', 'count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
