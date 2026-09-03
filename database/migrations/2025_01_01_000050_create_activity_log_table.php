<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail (03-technical-specs/03-database-schema.md §11) written by
 * the app/Support/Audit kernel — not the spatie/laravel-activitylog
 * package. context: admin|portal|api|system; action: create|update|
 * delete|login|export|publish; `changes` is a diff with sensitive fields
 * redacted (PII-masking convention, 04-modules/05-admin-panel.md).
 *
 * Retention: audit rows are kept 7 years (05-security-reliability.md) and
 * pruned/anonymized by retention:anonymize — hard deletes by policy, so
 * no soft deletes on this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->nullable();
            $table->string('context')->comment('admin|portal|api|system');
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->json('changes')->nullable()->comment('diff, sensitive fields redacted');
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['context', 'action']);
            $table->index('created_at');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
