<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Services (03-technical-specs/03-database-schema.md §3 + 04-modules/
 * 02-services-module.md §2): the 11-service catalog as a managed
 * self-referencing tree. `content_blocks` shares the CMS block library;
 * `faq` renders FAQPage schema; `lead_tag` flows into every form on the
 * page (Leads M3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug', 120);
            $table->string('family', 40)->index(); // employee-mobility|business-mobility|standalone
            $table->ulid('parent_id')->nullable();
            $table->string('name');
            $table->string('short_desc', 300)->nullable();
            $table->unsignedBigInteger('hero_media_id')->nullable();
            $table->text('intro')->nullable();
            $table->json('content_blocks')->nullable();
            $table->json('faq')->nullable();
            $table->string('icon_svg_key', 40)->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('sort')->default(0);
            $table->string('lead_tag', 60)->index();
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->boolean('noindex')->default(false);
            $table->text('noindex_reason')->nullable();
            $table->timestamp('noindex_confirmed_at')->nullable();
            $table->ulid('noindex_confirmed_by')->nullable();
            $table->string('locale', 5)->default('en')->index();
            $table->ulid('locale_source_id')->nullable();
            $table->string('cta_label_override', 60)->nullable();
            $table->ulid('created_by')->nullable();
            $table->ulid('updated_by')->nullable();
            $table->timestamps();

            // Locale-aware uniqueness (11-multilingual §4): variants share
            // the source slug (/ja/services/x resolves slug x, locale ja),
            // so uniqueness is per (slug, locale), never global.
            $table->unique(['slug', 'locale']);

            $table->foreign('parent_id')
                ->references('id')->on('services')
                ->nullOnDelete();

            // FULLTEXT (03-technical-specs/08-search.md §2) — MySQL only;
            // the test suite boots sqlite :memory: and SearchService
            // falls back to LIKE transparently (08-search §2 hybrid note).
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->fullText(['name', 'short_desc']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
