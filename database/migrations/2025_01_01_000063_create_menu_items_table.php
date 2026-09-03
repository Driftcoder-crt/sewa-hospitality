<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menu items (03-technical-specs/03-database-schema.md §2): a tree per
 * menu (parent_id + sort). `flagged` implements the menu-integrity rule
 * (04-modules/01-cms.md §5): deleting a linked entity auto-flags the
 * item for review — never a silently dead link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('menu_id');
            $table->ulid('parent_id')->nullable();
            $table->string('label');
            $table->string('url', 500)->nullable();
            $table->string('target', 10)->default('_self');
            $table->string('type', 20)->default('custom');
            $table->ulid('ref_id')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('flagged')->default(false);
            $table->timestamps();

            $table->foreign('menu_id')
                ->references('id')->on('menus')
                ->cascadeOnDelete();
            $table->index(['menu_id', 'parent_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
