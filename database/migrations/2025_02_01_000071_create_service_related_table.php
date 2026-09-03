<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * service_related pivot (04-modules/02-services-module.md §2): curated
 * cross-links ("You may also need") between service leaves — the
 * sibling-aware suggestion the reference lacks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_related', function (Blueprint $table) {
            $table->ulid('service_id');
            $table->ulid('related_id');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->primary(['service_id', 'related_id']);
            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
            $table->foreign('related_id')->references('id')->on('services')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_related');
    }
};
