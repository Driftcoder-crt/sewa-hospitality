<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Launch locales (03-technical-specs/03-database-schema.md §13 +
 * 04-modules/11-multilingual.md §1). `en` is the x-default and never
 * auto-translates; the other five auto-translate but only publish after
 * human review. Per-locale publishing is an M6 concern — this seeder
 * only registers the rows.
 *
 * Uses the query builder (no Locale model exists at M0 — the I18n module
 * arrives in M6) with an idempotent update-or-insert on the `code` PK,
 * so re-running is always safe.
 */
final class LocalesSeeder extends Seeder
{
    private const LOCALES = [
        ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'enabled' => true, 'fallback_for' => null, 'auto_translate' => false],
        ['code' => 'hi', 'name' => 'Hindi', 'native_name' => 'हिन्दी', 'direction' => 'ltr', 'enabled' => true, 'fallback_for' => null, 'auto_translate' => true],
        ['code' => 'ja', 'name' => 'Japanese', 'native_name' => '日本語', 'direction' => 'ltr', 'enabled' => true, 'fallback_for' => null, 'auto_translate' => true],
        ['code' => 'ko', 'name' => 'Korean', 'native_name' => '한국어', 'direction' => 'ltr', 'enabled' => true, 'fallback_for' => null, 'auto_translate' => true],
        ['code' => 'tr', 'name' => 'Turkish', 'native_name' => 'Türkçe', 'direction' => 'ltr', 'enabled' => true, 'fallback_for' => null, 'auto_translate' => true],
        ['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'enabled' => true, 'fallback_for' => null, 'auto_translate' => true],
    ];

    public function run(): void
    {
        foreach (self::LOCALES as $locale) {
            $query = DB::table('locales')->where('code', $locale['code']);

            if ($query->exists()) {
                $query->update([...$locale, 'updated_at' => now()]);

                continue;
            }

            DB::table('locales')->insert([
                ...$locale,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
