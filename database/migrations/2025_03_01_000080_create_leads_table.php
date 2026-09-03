<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leads — the money-path table (03-technical-specs/03-database-schema.md
 * §5 + 04-modules/03-leads-crm.md). Every form on the platform lands
 * here: transactional writes only, idempotency_key UNIQUE so network
 * retries can never duplicate a lead, SLA due time computed at create
 * (SlaPolicy), consent + policy version + hashed IP for privacy
 * (05-security-reliability §1.2).
 *
 * Recorded additive deviations (03-leads-crm §5 rules):
 *  - merged_into_lead_id: the 48h dedupe rule is a merge-into-existing
 *    FLAG with one-click review — never a silent drop, never a dup spam.
 *  - archived_at: bulk archive in the inbox (admin §4.1) without
 *    destroying pipeline history.
 *  - consent_version: privacy error lock pairs consent_at with the
 *    policy version that was in force at submission time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('source', 30)->index();   // contact|service_page|career_newsletter|portal_request|campaign|import
            $table->string('type', 30)->index();     // enquiry|newsletter|callback|quote_request|demo
            $table->string('name', 160);
            $table->string('email', 190)->index();
            $table->string('phone', 30)->nullable();
            $table->string('company', 160)->nullable();
            $table->text('message')->nullable();
            $table->ulid('service_id')->nullable();
            $table->ulid('city_id')->nullable();
            $table->string('locale', 8)->default('en');
            $table->string('status', 20)->default('new')->index(); // new|contacted|qualified|proposal|won|lost|nurture
            $table->string('lost_reason', 40)->nullable();
            $table->ulid('assigned_user_id')->nullable();
            $table->unsignedTinyInteger('score')->default(0);
            $table->json('enrichment')->nullable();
            $table->ulid('merged_into_lead_id')->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->timestamp('consent_at');
            $table->string('consent_version', 20)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('sla_due_at')->nullable()->index();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('next_action_at')->nullable()->index();
            $table->timestamp('archived_at')->nullable();
            $table->json('utm')->nullable();
            $table->timestamps();

            // ON DELETE RESTRICT for the money path (schema §12): a lead
            // must never lose its service/city context because someone
            // deleted a catalog row.
            $table->foreign('service_id')->references('id')->on('services')->restrictOnDelete();
            $table->foreign('city_id')->references('id')->on('cities')->restrictOnDelete();
            $table->foreign('assigned_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('merged_into_lead_id')->references('id')->on('leads')->nullOnDelete();

            $table->index(['status', 'sla_due_at']);
            $table->index(['assigned_user_id', 'status']);
            $table->index(['created_at', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
