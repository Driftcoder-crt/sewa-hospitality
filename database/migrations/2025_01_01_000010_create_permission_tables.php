<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spatie laravel-permission v6 tables on ULIDs (03-technical-specs/
 * 03-database-schema.md §1). We own this migration (package migrations
 * are never published) — ids are ULID so roles/permissions are
 * URL-exposable and time-sortable like every other Sewa row.
 *
 * SEWA ADDITIONS to the package schema: roles.slug + roles.display_name
 * (03-database-schema §1 — stable key + human label for the admin UI).
 * Everything else mirrors the package default: pivot FKs cascade on
 * delete/update semantics per package default (cascadeOnDelete), composite
 * PKs on the three pivot tables, (model_id, model_type) index via
 * ulidMorphs. No teams feature at launch (memberships start empty).
 *
 * Seed (§1 / 04-modules/05-admin-panel.md): super-admin, admin, editor,
 * hr-manager, recruiter, finance, consultant, client-manager,
 * client-employee, author.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name')->unique();
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name')->index();
            $table->string('guard_name');
            // Sewa additions (03-database-schema §1): stable key + label.
            $table->string('slug')->nullable()->unique();
            $table->string('display_name')->nullable();
            $table->timestamps();
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->ulid('permission_id');
            $table->ulidMorphs('model');
            $table->primary(['permission_id', 'model_id', 'model_type']);

            $table->foreign('permission_id')
                ->references('id')->on('permissions')
                ->cascadeOnDelete();
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->ulid('role_id');
            $table->ulidMorphs('model');
            $table->primary(['role_id', 'model_id', 'model_type']);

            $table->foreign('role_id')
                ->references('id')->on('roles')
                ->cascadeOnDelete();
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->ulid('permission_id');
            $table->ulid('role_id');
            $table->primary(['permission_id', 'role_id']);

            $table->foreign('permission_id')
                ->references('id')->on('permissions')
                ->cascadeOnDelete();
            $table->foreign('role_id')
                ->references('id')->on('roles')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
