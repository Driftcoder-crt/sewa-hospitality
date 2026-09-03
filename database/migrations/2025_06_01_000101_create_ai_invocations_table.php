<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ai_invocations — budget + audit ledger (03-technical-specs/
 * 03-database-schema.md §10 + 08-ai-system/01-ai-architecture.md §3).
 *
 * One row per gateway call ("One invocation = one log row showing which
 * path served" — provider column records the serving provider, meta
 * records the failover path when a fallback served). Append-only:
 * created_at only, no updates. Retention 90 days (01-ai-architecture §5
 * DPDP posture — purge is wired by the retention sweeper).
 *
 * PII rule (01-ai-architecture §5): metadata + hashes only — no prompts,
 * no completions, no raw client PII. `meta` carries feature-scoped,
 * non-sensitive keys (entity type/id reference, breaker state, locale).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_invocations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->nullable()->comment('users.id — acting admin/consultant when human-triggered');
            $table->string('feature', 20)->comment('translate|enrich|summarize|draft|score (08-ai-system/01 §4 map)');
            $table->string('provider', 40)->comment('config provider id that served the call');
            $table->string('model', 80)->comment('model slug that served the call');
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->unsignedBigInteger('cost_estimate')->default(0)->comment('integer paise — error lock #6, never float');
            $table->string('status', 12)->comment('ok|fallback|error');
            $table->unsignedInteger('latency_ms')->default(0);
            $table->json('meta')->nullable()->comment('non-sensitive metadata only — PII guard strips before write');
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['feature', 'status']);

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_invocations');
    }
};
