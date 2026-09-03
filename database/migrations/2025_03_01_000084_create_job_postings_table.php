<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * job_postings (03-database-schema §4 + 04-modules/06-hr-employee-module
 * §3): the hiring funnel source of truth. Closed postings KEEP their URL
 * forever (history/SEO — never a 404, §5) so the slug is stable and the
 * status machine (draft→open→paused→closed) only changes what renders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug', 190)->unique();
            $table->string('title', 190);
            $table->string('department', 20)->index(); // relocation|immigration|fleet|housing|finance|hr|ops|tech
            $table->ulid('location_city_id')->nullable();
            $table->string('location_text', 160);
            $table->string('employment_type', 12)->index(); // full|part|contract|intern
            $table->unsignedTinyInteger('experience_min')->nullable();
            $table->unsignedTinyInteger('experience_max')->nullable();
            $table->text('description_html')->nullable();
            $table->text('responsibilities_html')->nullable();
            $table->text('skills_html')->nullable();
            $table->string('status', 12)->default('draft')->index(); // draft|open|paused|closed
            $table->date('closes_at')->nullable()->index();
            $table->ulid('posted_by_user_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('applies_to_email', 190)->nullable();
            $table->string('locale', 8)->default('en');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->foreign('location_city_id')->references('id')->on('cities')->nullOnDelete();
            $table->foreign('posted_by_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['status', 'department', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
