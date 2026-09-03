<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Housing inventory (03-technical-specs/03-database-schema.md §3 +
 * 04-modules/10-cities-content.md): serviced apartments, corporate
 * housing, guest houses. Rates are honest ranges ("from ₹X"), stored as
 * INTEGER INR (paise doctrine does not apply to published from-rates —
 * they are display values, not billed amounts; Billing M5 keeps its own
 * integer-paise ledger). verified_at drives the Sewa Verified badge;
 * 90-day staleness flag computed on read.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housing_units', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('city_id');
            $table->string('type', 30)->index(); // serviced-apartment|corporate-housing|guest-house
            $table->string('name');
            $table->string('area', 120)->nullable();
            $table->string('locality', 120)->nullable();
            $table->unsignedTinyInteger('bedrooms')->default(1)->index();
            $table->string('tier', 20)->index(); // essential|professional|executive
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('from_rate')->nullable(); // INR, display "from"
            $table->string('rate_unit', 10)->default('night'); // night|month
            $table->unsignedInteger('area_sqft')->nullable();
            $table->json('amenities')->nullable();
            $table->json('media_ids')->nullable();
            $table->timestamp('verified_at')->nullable()->index();
            $table->ulid('verified_by_user_id')->nullable();
            $table->string('managed_by', 120)->nullable();
            $table->boolean('published')->default(false)->index();
            $table->text('notes')->nullable();
            $table->ulid('created_by')->nullable();
            $table->ulid('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('city_id')->references('id')->on('cities')->restrictOnDelete();
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->fullText(['name', 'locality', 'area']);
            }
            $table->index(['published', 'city_id', 'type', 'tier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housing_units');
    }
};
