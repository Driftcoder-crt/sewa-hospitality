<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * author_profiles (03-database-schema §4): one profile per user with the
 * `author` role — bio, credentials, LinkedIn — feeding blog bylines and
 * Person schema from M4. The authorship rule (posts cannot publish
 * without a human author) makes this table load-bearing, not cosmetic.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_profiles', function (Blueprint $table) {
            $table->ulid('user_id')->primary();
            $table->string('title', 120)->nullable();
            $table->text('bio')->nullable();
            $table->json('credentials')->nullable();
            $table->string('linkedin', 255)->nullable();
            $table->unsignedBigInteger('photo_media_id')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('photo_media_id')->references('id')->on('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_profiles');
    }
};
