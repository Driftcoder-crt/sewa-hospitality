<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CMS pages (03-technical-specs/03-database-schema.md §2 "pages").
 * `blocks` holds the ordered typed-block JSON (the CMS "Lego");
 * `template` selects the public blade view key; locale fields are
 * created now and wired to the translation pipeline in M6
 * (04-modules/11-multilingual.md).
 *
 * Additive deviation (recorded per least-deviation doctrine): the
 * noindex audit columns — noindex_confirmed_at/_by — implement
 * 04-modules/01-cms.md §5 ("noindex requires typed confirmation +
 * reason, logged"); the base schema lists only the `noindex` flag.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug', 191);
            $table->string('title');
            $table->string('type', 20)->default('standard')->index();
            $table->ulid('parent_id')->nullable();
            $table->string('template', 60)->default('default');
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();

            // SEO gates (04-modules/01-cms.md §5 — publish enforcement).
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->unsignedBigInteger('og_image_media_id')->nullable();
            $table->boolean('noindex')->default(false);
            $table->text('noindex_reason')->nullable();
            $table->timestamp('noindex_confirmed_at')->nullable();
            $table->ulid('noindex_confirmed_by')->nullable();
            $table->string('canonical_override', 500)->nullable();

            $table->json('blocks')->nullable();
            $table->string('locale', 5)->default('en')->index();
            $table->ulid('locale_source_id')->nullable();
            $table->ulid('author_user_id')->nullable();
            $table->ulid('created_by')->nullable();
            $table->ulid('updated_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index(['type', 'status', 'locale']);

            // Locale-aware uniqueness (11-multilingual §4): translation
            // variants SHARE the source slug — /ja/about resolves the ja
            // row of slug 'about' — so uniqueness is per (slug, locale),
            // never global.
            $table->unique(['slug', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
