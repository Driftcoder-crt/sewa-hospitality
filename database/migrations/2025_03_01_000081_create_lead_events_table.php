<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * lead_events — the lead timeline (03-database-schema §5): notes, calls,
 * emails sent, status changes, assignments, system events (SLA breach,
 * escalation). Append-only: the CRM's memory of "what happened, when".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('lead_id');
            $table->ulid('user_id')->nullable();
            $table->string('type', 20); // note|status|email|call|sms|form|assign|system
            $table->json('payload')->nullable();
            $table->timestamp('created_at');

            $table->foreign('lead_id')->references('id')->on('leads')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['lead_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_events');
    }
};
