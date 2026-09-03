<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auth tables (03-technical-specs/03-database-schema.md §1):
 * users (ULID pk), password_reset_tokens, sessions, and Sanctum's
 * personal_access_tokens (ULID pk + ULID morphs — every tokenable is a
 * ULID model; API scopes live in `abilities`: portal.read, app.write, …).
 *
 * FK (§12): users.avatar_media_id → media.id is a soft reference —
 * nullOnDelete. NOTE: media intentionally keeps its Spatie integer PK
 * (accepted documented deviation), hence the unsignedBigInteger here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('avatar_media_id')->nullable()->comment('Spatie media keeps int PK — accepted documented deviation');
            $table->string('locale')->default('en');
            $table->string('timezone')->default('Asia/Kolkata');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->string('status')->default('active')->comment('active|invited|disabled — app-cast enum');
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('avatar_media_id')->references('id')->on('media')->nullOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->ulid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulidMorphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
