<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * newsletter_subscribers (03-database-schema §5): double opt-in is the
 * ONLY path to `confirmed` — marketing email goes nowhere else
 * (10-email.md §1.4, CAN-SPAM/DPDP hygiene). One token serves both the
 * confirm link and the one-click unsubscribe link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('email', 190)->unique();
            $table->string('status', 20)->default('pending')->index(); // pending|confirmed|unsubscribed|bounced
            $table->string('token', 64)->unique();
            $table->string('locale', 8)->default('en');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('source', 40)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
