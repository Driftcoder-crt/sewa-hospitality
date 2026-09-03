<?php

use Database\Seeders\LocalesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('seeds exactly the six launch locales, all enabled', function () {
    $this->seed(LocalesSeeder::class);

    expect(DB::table('locales')->count())->toBe(6)
        ->and(DB::table('locales')->where('enabled', true)->count())->toBe(6);

    $nativeNames = DB::table('locales')->pluck('native_name', 'code');

    expect($nativeNames['en'])->toBe('English')
        ->and($nativeNames['hi'])->toBe('हिन्दी')
        ->and($nativeNames['ja'])->toBe('日本語')
        ->and($nativeNames['ko'])->toBe('한국어')
        ->and($nativeNames['tr'])->toBe('Türkçe')
        ->and($nativeNames['ar'])->toBe('العربية');
});

it('marks ar as rtl, en as the untranslated default and hi as auto-translated', function () {
    $this->seed(LocalesSeeder::class);

    $ar = DB::table('locales')->where('code', 'ar')->first();
    $en = DB::table('locales')->where('code', 'en')->first();
    $hi = DB::table('locales')->where('code', 'hi')->first();

    expect($ar->direction)->toBe('rtl')
        ->and($en->direction)->toBe('ltr')
        ->and((bool) $en->auto_translate)->toBeFalse()
        ->and((bool) $hi->auto_translate)->toBeTrue();
});

it('is idempotent — re-running the seeder never duplicates locales', function () {
    $this->seed(LocalesSeeder::class);
    $this->seed(LocalesSeeder::class);

    expect(DB::table('locales')->count())->toBe(6)
        ->and((bool) DB::table('locales')->where('code', 'en')->value('auto_translate'))->toBeFalse();
});
