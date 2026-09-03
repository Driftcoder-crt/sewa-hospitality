<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Translation reject-with-restore (04-modules/11-multilingual.md §6.2):
 * reject returns a row to machine state FOR RE-DRAFTING — the human
 * edit must not survive as the served value. The pre-approval machine
 * value is preserved next to the row so reject can restore it across
 * processes (queue workers, next request), not just in-memory.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('translations', function (Blueprint $table) {
            $table->text('machine_value')->nullable()->after('value')
                ->comment('machine draft preserved for reject-restore');
        });
    }

    public function down(): void
    {
        Schema::table('translations', function (Blueprint $table) {
            $table->dropColumn('machine_value');
        });
    }
};
