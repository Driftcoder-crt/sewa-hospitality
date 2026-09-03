<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Portal notifications (03-database-schema.md §8 + 04-modules/
 * 04-client-portal.md §3): the notification-center rows — stage
 * changes, published documents, invoice issued, replies. Realtime
 * badge via NotificationCreated event; wire:poll is the always-works
 * transport (11-realtime §3).
 *
 * FK: user nullOnDelete (an account removal never deletes history —
 * retention:anonymize owns lifecycle); index (user_id, read_at) is
 * the badge/unread-list query.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_notifications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // Nullable per the retention rule in this file's docblock:
            // account removal sets NULL (retention:anonymize owns the
            // lifecycle) — and MySQL rejects ON DELETE SET NULL on a
            // NOT NULL column outright (errno 150).
            $table->ulid('user_id')->nullable();
            $table->string('title', 190);
            $table->text('body')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('kind', 24)->default('general')
                ->comment('stage|document|message|invoice|checklist|general');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_notifications');
    }
};
