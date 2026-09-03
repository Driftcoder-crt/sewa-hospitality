<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Site-scope key/value settings (03-technical-specs/03-database-schema.md
 * §2). Groups: brand|contact|seo|integrations|legal. Seeds (M0-d) include
 * the organization identity JSON, NAP, socials, offices list pointer,
 * counters, membership badges and analytics ids
 * (01-platform-vision/02-brand-sewa-hospitality.md §9 — NAP single source).
 *
 * `group` is a MySQL reserved word; the Schema builder always quotes
 * identifiers, and the Setting model references it explicitly, so this is
 * safe on both MySQL 8 and sqlite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('group')->default('brand')->index();
            $table->string('editable_by_role')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
