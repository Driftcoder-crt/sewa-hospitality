<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cities (03-technical-specs/03-database-schema.md §3 + 04-modules/
 * 10-cities-content.md §2): the all-India city program. FULLTEXT on
 * (name, description) per the search spec. Wave-1 hubs seeded (city
 * content program §W1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug', 120);
            $table->string('name');
            $table->string('state');
            $table->string('country', 2)->default('IN');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('is_hub')->default(false)->index();
            $table->text('description')->nullable();
            $table->json('content_blocks')->nullable();
            $table->unsignedBigInteger('hero_media_id')->nullable();
            $table->unsignedBigInteger('population')->nullable();
            $table->decimal('cost_index', 6, 2)->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->string('locale', 5)->default('en')->index();
            $table->ulid('locale_source_id')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->boolean('noindex')->default(false);
            $table->text('noindex_reason')->nullable();
            $table->timestamp('noindex_confirmed_at')->nullable();
            $table->ulid('noindex_confirmed_by')->nullable();
            $table->ulid('created_by')->nullable();
            $table->ulid('updated_by')->nullable();
            $table->timestamps();

            // Locale-aware uniqueness (11-multilingual §4): variants share
            // the source slug (/ja/cities/x resolves slug x, locale ja),
            // so uniqueness is per (slug, locale), never global.
            $table->unique(['slug', 'locale']);

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->fullText(['name', 'description']);
            }
            $table->index(['status', 'is_hub']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
