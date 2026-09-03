<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * city_services pivot (03-technical-specs/03-database-schema.md §3 +
 * 04-modules/10-cities-content.md §5 "coverage truth"): a service listed
 * on a city page MUST have a row here — no optimistic coverage. Notes
 * carry local specifics ("Fleet: 40 vehicles in Pune").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_services', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('city_id');
            $table->ulid('service_id');
            $table->string('note', 300)->nullable();
            $table->timestamps();

            $table->unique(['city_id', 'service_id']);
            $table->foreign('city_id')->references('id')->on('cities')->cascadeOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
            $table->index('service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_services');
    }
};
