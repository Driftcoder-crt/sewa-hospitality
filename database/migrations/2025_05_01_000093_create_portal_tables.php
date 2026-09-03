<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Client portal tables (03-database-schema.md §8 + 04-modules/
 * 04-client-portal.md §2): moves, documents, threads/messages,
 * checklist items — tenant scoping root is organization_id.
 *
 * FK semantics (§12): organization/move references are RESTRICT (portal
 * history is never silently destroyed — orgs archive instead of delete);
 * user references nullOnDelete (removing an account never deletes
 * portal records). Messages cascade with their thread (append-only pair).
 *
 * ADDITIVE DEVIATION (recorded): portal_move_records.reference (unique,
 * SEWA-M-YYYY-####) — review_requests.move_reference is UNIQUE with the
 * one-chain-per-move invariant (08 doc §4.3), so moves need a stable
 * natural key for the review-request engine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_move_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('reference', 24)->unique();
            $table->ulid('organization_id');
            $table->ulid('employee_user_id')->nullable();
            $table->ulid('primary_consultant_user_id')->nullable();
            $table->string('assignee_name')->nullable();
            $table->string('assignee_email')->nullable();
            $table->string('origin_city', 120)->nullable();
            $table->ulid('destination_city_id')->nullable();
            $table->date('move_date')->nullable();
            $table->string('stage', 16)->default('intake')->index()
                ->comment('intake|planning|in-progress|settling|complete|closed');
            $table->string('status', 12)->default('active')->index()
                ->comment('active|on_hold|cancelled');
            $table->text('summary')->nullable();
            $table->json('service_ids')->nullable();
            $table->json('timeline')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('employee_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('primary_consultant_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('destination_city_id')->references('id')->on('cities')->nullOnDelete();

            $table->index(['organization_id', 'stage']);
            $table->index('primary_consultant_user_id');
        });

        Schema::create('portal_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('move_record_id')->nullable();
            $table->ulid('organization_id');
            $table->ulid('user_id')->nullable();
            $table->ulid('uploaded_by')->nullable();
            $table->string('title', 190);
            $table->unsignedBigInteger('media_id');
            $table->string('category', 16)->index()->comment('visa|lease|inventory|invoice|other');
            $table->string('visible_to', 12)->default('both')->comment('employee|manager|both');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('move_record_id')->references('id')->on('portal_move_records')->restrictOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('media_id')->references('id')->on('media')->restrictOnDelete();

            $table->index(['organization_id', 'category']);
        });

        Schema::create('portal_threads', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('move_record_id')->nullable();
            $table->ulid('organization_id')->nullable();
            $table->string('subject', 190)->nullable();
            $table->string('status', 12)->default('open')->index()->comment('open|closed');
            $table->ulid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('move_record_id')->references('id')->on('portal_move_records')->restrictOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('portal_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('thread_id');
            $table->ulid('sender_user_id')->nullable();
            $table->string('sender_role', 12)->default('client')->comment('client|consultant|system');
            $table->text('body');
            $table->json('media_ids')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('thread_id')->references('id')->on('portal_threads')->cascadeOnDelete();
            $table->foreign('sender_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['thread_id', 'created_at']);
        });

        Schema::create('portal_checklist_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('move_record_id');
            $table->string('title', 190);
            $table->text('detail')->nullable();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('done_at')->nullable();
            $table->ulid('done_by')->nullable();
            $table->integer('sort')->default(0);
            $table->string('status', 12)->default('pending')->index()->comment('pending|done');

            $table->foreign('move_record_id')->references('id')->on('portal_move_records')->cascadeOnDelete();
            $table->foreign('done_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['move_record_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_checklist_items');
        Schema::dropIfExists('portal_messages');
        Schema::dropIfExists('portal_threads');
        Schema::dropIfExists('portal_documents');
        Schema::dropIfExists('portal_move_records');
    }
};
