<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Testimonials + Google review cache (03-database-schema §6 +
 * 04-modules/08-testimonials-reviews.md): verification discipline —
 * "verified" only when linked to a synced Google review or a completed
 * move; consent flag gates name/company display; machine-translated
 * bodies stay hidden until reviewed. google_reviews is the GBP sync
 * CACHE keyed by external_id (idempotent double-cron).
 *
 * Additive recorded deviation: review_requests table implements the
 * "request queue, sent/opened/done tracking" (08 doc §4.3) — one
 * completion = one request chain, ever.
 */
return new class extends Migration
{
    public function up(): void
    {
        // google_reviews first — testimonials FKs reference it, and
        // MySQL/MariaDB reject an FK to a table that doesn't exist yet
        // (errno 150; SQLite tolerates it, which is why the test suite
        // never caught the order).
        Schema::create('google_reviews', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('external_id', 190)->unique();
            $table->unsignedTinyInteger('rating');
            $table->text('text')->nullable();
            $table->string('reviewer', 160)->nullable();
            $table->timestamp('review_at')->nullable();
            $table->string('url', 300)->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->boolean('synced')->default(false);
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('client_name', 160);
            $table->string('client_role', 120)->nullable();
            $table->string('company', 160)->nullable();
            $table->ulid('city_id')->nullable();
            $table->ulid('service_id')->nullable();
            $table->text('body');
            $table->unsignedTinyInteger('rating')->nullable(); // only when actually rated
            $table->string('source', 20)->index(); // google|direct|email|form
            $table->string('source_url', 300)->nullable();
            $table->ulid('google_review_id')->nullable();
            $table->boolean('consent_named')->default(false); // name/company display gate
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('status', 12)->default('pending')->index(); // pending|published|archived
            $table->string('locale', 8)->default('en');
            $table->ulid('locale_source_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('city_id')->references('id')->on('cities')->nullOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
            $table->foreign('google_review_id')->references('id')->on('google_reviews')->nullOnDelete();

            $table->index(['status', 'service_id', 'city_id']);
        });

        Schema::create('review_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('move_record_id')->nullable(); // portal M5 links in
            $table->string('move_reference', 190)->unique(); // idempotency anchor pre-portal
            $table->string('recipient_email', 190);
            $table->string('recipient_name', 160)->nullable();
            $table->string('status', 16)->default('queued')->index(); // queued|sent|followed_up|done|failed
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('follow_up_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_requests');
        Schema::dropIfExists('google_reviews');
        Schema::dropIfExists('testimonials');
    }
};
