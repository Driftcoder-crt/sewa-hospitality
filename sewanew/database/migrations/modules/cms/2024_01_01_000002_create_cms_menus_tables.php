<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cms_menus', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('location')->unique(); // header, footer, sidebar
            $table->boolean('is_active')->default(true);
            $table->json('items')->nullable(); // Menu structure as JSON
            $table->timestamps();
            $table->ulid('created_by')->nullable();
            $table->ulid('updated_by')->nullable();
        });

        Schema::create('cms_menu_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('menu_id')->constrained('cms_menus')->onDelete('cascade');
            $table->string('title');
            $table->string('url')->nullable();
            $table->ulid('page_id')->nullable()->constrained('cms_pages')->onDelete('set null');
            $table->ulid('parent_id')->nullable()->constrained('cms_menu_items')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->string('icon')->nullable();
            $table->boolean('is_external')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->json('attributes')->nullable();
            $table->timestamps();

            $table->index('menu_id');
            $table->index('parent_id');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_menu_items');
        Schema::dropIfExists('cms_menus');
    }
};
