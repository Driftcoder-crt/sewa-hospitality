<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Translations — UI-string cache (03-technical-specs/03-database-schema.md
 * §10 + 04-modules/11-multilingual.md §2/§4). Key-value rows per
 * locale+namespace+key with the machine|human-reviewed gate: machine
 * strings never publish to public surfaces without human approval
 * (11-multilingual §4 — "a blank or machine-only page NEVER publishes
 * as final").
 *
 * Content entities are NOT here — they carry locale + locale_source_id
 * translation groups directly (§10: "instead of separate tables").
 *
 * Columns follow the spec exactly: only updated_at (a translation is
 * written on seed/import and touched on review — creation time carries
 * no review meaning), unique (locale, namespace, key).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('locale', 12)->comment('locales.code');
            $table->string('namespace', 20)->comment('site|portal|admin|email');
            $table->string('key');
            $table->text('value');
            $table->string('status')->default('machine')->comment('machine|human-reviewed');
            $table->ulid('reviewed_by')->nullable()->comment('users.id — reviewer attribution (11-multilingual §6.2)');
            $table->timestamp('updated_at')->nullable();

            $table->unique(['locale', 'namespace', 'key']);

            $table->foreign('locale')->references('code')->on('locales')->restrictOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
