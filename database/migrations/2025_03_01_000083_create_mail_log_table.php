<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * mail_log (03-technical-specs/10-email.md §6–7): every template send
 * carries a deterministic idempotency key (e.g. "lead.ack:{id}") — the
 * dispatcher checks this table BEFORE sending, so cron double-fires and
 * queue retry storms can never double-send. Recipients are stored
 * hashed (privacy lock #5), never as live addresses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_log', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('key', 120)->unique();
            $table->string('template', 60)->index();
            $table->string('recipient_hash', 64)->nullable();
            $table->string('status', 20)->default('sent'); // sent|failed
            $table->string('provider_message_id', 190)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_log');
    }
};
