<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Billing tables (03-database-schema.md §9 + 04-modules/
 * 12-billing-finance.md §2): quotes, invoices, invoice_payments.
 *
 * Money discipline (error-locks §2.1): ALL amounts are INTEGER paise —
 * no floats anywhere; numbering SEWA-Q/I-YYYY-#### is allocated under a
 * locked transaction (App\Modules\Billing\Services\SequentialNumbering)
 * and void keeps the number (statutory hygiene).
 *
 * FK semantics (§12): RESTRICT on every financial link — invoices,
 * payments and quotes are hard-deleted never; orgs archive instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('number', 20)->unique();
            $table->ulid('organization_id');
            $table->ulid('move_record_id')->nullable();
            $table->ulid('lead_id')->nullable();
            $table->string('status', 12)->default('draft')->index()
                ->comment('draft|sent|accepted|expired|rejected');
            $table->json('lines');
            $table->integer('total')->default(0)->comment('paise — integers only');
            $table->string('currency', 3)->default('INR');
            $table->date('valid_until')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->string('token', 64)->nullable()->unique()->comment('accept/reject link token');
            $table->integer('version')->default(1)->comment('bumped on edits after send (audit trail)');
            $table->ulid('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('move_record_id')->references('id')->on('portal_move_records')->nullOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('number', 20)->unique();
            $table->ulid('quote_id')->nullable();
            $table->ulid('organization_id');
            $table->ulid('move_record_id')->nullable();
            $table->string('status', 12)->default('draft')->index()
                ->comment('draft|sent|partial|paid|overdue|void');
            $table->json('lines');
            $table->integer('subtotal')->default(0)->comment('paise');
            $table->json('tax_breakdown')->nullable()->comment('per tax class totals');
            $table->integer('total')->default(0)->comment('paise');
            $table->string('currency', 3)->default('INR');
            $table->date('due_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->integer('reminders_sent')->default(0);
            $table->timestamp('last_reminder_at')->nullable();
            $table->string('void_reason', 300)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('quote_id')->references('id')->on('quotes')->nullOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('move_record_id')->references('id')->on('portal_move_records')->nullOnDelete();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('invoice_id');
            $table->string('method', 12)->comment('bank|upi|cheque|gateway');
            $table->integer('amount')->comment('paise');
            $table->date('paid_at')->index();
            $table->string('reference', 190)->nullable()->index();
            $table->ulid('recorded_by')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('quotes');
    }
};
