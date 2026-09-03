<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Redirects (03-technical-specs/03-database-schema.md §2 + 04-modules/
 * 01-cms.md §4.5): from (unique, normalized path) → to, 301|302, hit
 * counter, note. The `active` flag lets editors stage entries without
 * serving them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('from', 500)->unique();
            $table->string('to', 500);
            $table->unsignedSmallInteger('code')->default(301);
            $table->unsignedBigInteger('hits')->default(0);
            $table->string('note', 500)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
