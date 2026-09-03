<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cms_media', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->unsignedBigInteger('size');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('caption')->nullable();
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->ulid('uploaded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('disk');
            $table->index('mime_type');
            $table->index('uploaded_by');
        });

        Schema::create('cms_media_collections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->ulid('cover_image_id')->nullable()->constrained('cms_media')->onDelete('set null');
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            $table->ulid('created_by')->nullable();
        });

        Schema::create('cms_collection_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('collection_id')->constrained('cms_media_collections')->onDelete('cascade');
            $table->ulid('media_id')->constrained('cms_media')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->unique(['collection_id', 'media_id']);
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_collection_items');
        Schema::dropIfExists('cms_media_collections');
        Schema::dropIfExists('cms_media');
    }
};
