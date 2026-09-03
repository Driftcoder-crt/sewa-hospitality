<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CSR (03-database-schema §7 + 04-modules/09-csr-module.md): named NGO
 * partners with real link-outs, measurable claims carrying "as of"
 * dates (claims ledger §4.4), citable impact stories. Archived partners
 * stay visible as "past associations" — honesty over logo walls.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ngo_partners', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 160);
            $table->string('slug', 190)->unique();
            $table->unsignedBigInteger('logo_media_id')->nullable();
            $table->string('website', 300)->nullable();
            $table->text('description')->nullable();
            $table->json('focus_areas')->nullable();
            $table->string('claim', 300)->nullable(); // measurable claim
            $table->string('claim_as_of', 40)->nullable(); // "as of" discipline
            $table->string('claim_source', 300)->nullable();
            $table->year('since')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('status', 12)->default('active')->index(); // active|archived
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('locale', 8)->default('en');
            $table->ulid('locale_source_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('logo_media_id')->references('id')->on('media')->nullOnDelete();
        });

        Schema::create('csr_stories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug', 190);
            $table->string('title', 190);
            $table->longText('body');
            $table->json('media_ids')->nullable();
            $table->ulid('ngo_partner_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('status', 12)->default('draft')->index(); // draft|published
            $table->boolean('cross_post_to_blog')->default(false);
            $table->string('locale', 8)->default('en');
            $table->ulid('locale_source_id')->nullable();
            $table->ulid('author_user_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('ngo_partner_id')->references('id')->on('ngo_partners')->nullOnDelete();
            $table->foreign('author_user_id')->references('id')->on('users')->nullOnDelete();

            // Locale-aware uniqueness (11-multilingual §4): variants share
            // the source slug (/ja/csr/x resolves slug x, locale ja), so
            // uniqueness is per (slug, locale), never global.
            $table->unique(['slug', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('csr_stories');
        Schema::dropIfExists('ngo_partners');
    }
};
