<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Locales (03-technical-specs/03-database-schema.md §10). The locale code
 * IS the primary key — 'code (pk: en|hi|ja|ko|tr|ar)' — NOT a ULID: it is
 * a stable public key used in URL prefixes (/ko /ja /tr /ar), hreflang
 * and the translation pipeline. Seeds (M0-d / §13): en (x-default), hi,
 * ja, ko, tr, ar (RTL).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locales', function (Blueprint $table) {
            $table->string('code', 12)->primary();
            $table->string('name');
            $table->string('native_name');
            $table->string('direction')->default('ltr')->comment('ltr|rtl');
            $table->boolean('enabled')->default(true);
            $table->string('fallback_for', 12)->nullable();
            $table->boolean('auto_translate')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locales');
    }
};
