<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * employees — the people registry (03-database-schema §4, 06-hr doc
 * §4.3): internal directory + the is_public flag that feeds the About
 * page, leadership grids (D6 block) and service-page consultant cards.
 * "Real names, real people, real E-E-A-T." Leave/appraisal internals
 * are phase 2 (schema reserved, not built — out-of-scope list).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->nullable();
            $table->string('employee_code', 20)->unique();
            $table->string('full_name', 160);
            $table->string('designation', 120);
            $table->string('department', 20)->index();
            $table->date('joined_at')->nullable();
            $table->string('employment_type', 12)->default('full');
            $table->ulid('office_city_id')->nullable();
            $table->boolean('is_public')->default(false)->index();
            $table->text('bio')->nullable();
            $table->json('credentials')->nullable(); // certifications, years, languages…
            $table->unsignedBigInteger('photo_media_id')->nullable();
            $table->ulid('manager_employee_id')->nullable();
            $table->string('status', 12)->default('active')->index(); // active|notice|alumni
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('office_city_id')->references('id')->on('cities')->nullOnDelete();
            $table->foreign('photo_media_id')->references('id')->on('media')->nullOnDelete();
            $table->foreign('manager_employee_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
