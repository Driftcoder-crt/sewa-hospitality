<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * job_applications — the ATS-lite pipeline (03-database-schema §4).
 * PII discipline: resumes are stored on the PRIVATE local disk
 * (resume_path — recorded deviation; schema's media id stays nullable
 * for a future medialibrary move) and previewed only via signed URLs —
 * never through the public media subdomain. idempotency_key UNIQUE so a
 * flaky mobile network cannot double-apply; consent_at + version logged
 * per privacy error lock #5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('job_posting_id')->nullable();
            $table->string('applicant_name', 160);
            $table->string('applicant_email', 190)->index();
            $table->string('applicant_phone', 30)->nullable();
            $table->string('resume_path', 255)->nullable();
            $table->unsignedBigInteger('resume_media_id')->nullable();
            $table->text('cover_message')->nullable();
            $table->string('source', 20)->default('site'); // site|campaign
            $table->string('source_detail', 190)->nullable(); // which job page/campaign
            $table->string('status', 20)->default('new')->index(); // new|screening|shortlisted|interview|offer|hired|rejected|withdrawn
            $table->unsignedTinyInteger('rating')->nullable();
            $table->json('notes')->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->timestamp('consent_at');
            $table->string('consent_version', 20)->nullable();
            $table->timestamps();

            $table->foreign('job_posting_id')->references('id')->on('job_postings')->restrictOnDelete();
            $table->foreign('resume_media_id')->references('id')->on('media')->nullOnDelete();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
