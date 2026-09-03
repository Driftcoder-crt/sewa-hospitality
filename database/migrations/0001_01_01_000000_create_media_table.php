<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Media table — OWNED by Sewa. We never publish Spatie's medialibrary
 * migration; this file reproduces the package contract exactly (column
 * names/int types the package and its jobs depend on) and appends the
 * Sewa app columns (03-technical-specs/03-database-schema.md §2 +
 * 03-technical-specs/09-media-pipeline.md).
 *
 * Accepted documented deviation: media keeps Spatie's integer PK while
 * every other Sewa table uses ULIDs. Media is exposed by `uuid` for
 * URLs and referenced by int id from users.avatar_media_id.
 *
 * Alt-text discipline: `alt_text` is required at upload (enforced by
 * admin validation); an empty string is only ever stored together with
 * is_decorative=true — intentional, never accidental.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            // --- Spatie MediaLibrary package contract (do not rename) ---
            $table->bigIncrements('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('model_type')->index();
            $table->unsignedBigInteger('model_id')->index();
            $table->index(['model_type', 'model_id']);
            $table->string('collection_name')->index();
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk');
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions')->nullable();
            $table->json('responsive_images');
            $table->unsignedBigInteger('order_column')->nullable()->index();
            $table->timestamps();

            // --- Sewa app columns (03-database-schema.md §2) ---
            $table->string('alt_text')->default('')->comment("required at upload — enforced by validation; '' only with is_decorative=true");
            $table->boolean('is_decorative')->default(false);
            $table->string('credit')->nullable();
            $table->string('focal_point')->nullable()->comment('percentages "x,y"');
            $table->string('namespace')->nullable()->index()->comment('brand|services|cities|housing|blog|team|csr|testimonials|careers|portal|legal');
            $table->boolean('person_consent')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
