<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Client companies + membership pivot (03-technical-specs/
 * 03-database-schema.md §1). organizations is the portal tenant root
 * (tenant isolation, M5); organization_users carries the portal role.
 *
 * FK semantics (§12): organization_users is a true child pivot →
 * cascadeOnDelete on both parent FKs; crm_owner_user_id / invited_by are
 * soft references → nullOnDelete (deleting a user must never delete
 * organization history).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('industry')->nullable();
            $table->string('gstin')->nullable();
            $table->string('pan')->nullable();
            $table->json('billing_address')->nullable();
            $table->string('status')->default('active')->index()->comment('active|prospect|archived');
            $table->text('notes')->nullable();
            $table->ulid('crm_owner_user_id')->nullable();
            $table->timestamps();

            $table->foreign('crm_owner_user_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });

        Schema::create('organization_users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->ulid('user_id');
            $table->string('role_in_org')->default('employee')->comment('manager|employee|billing');
            $table->ulid('invited_by')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
            $table->index('role_in_org');

            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
            $table->foreign('invited_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_users');
        Schema::dropIfExists('organizations');
    }
};
