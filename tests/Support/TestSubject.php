<?php

namespace Tests\Support;

use App\Models\Concerns\HasSewaMedia;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

/**
 * Minimal media-owning model for the pipeline tests (RefreshDatabase
 * creates the `test_subjects` table in each suite's bootstrap). It exists
 * outside the app namespaces on purpose: it is test scaffolding, not a
 * content model, and must never leak into production autoload maps.
 *
 * Uses the platform-wide HasSewaMedia concern so the tests exercise the
 * REAL conversion set (thumb/card/hero/hero-avif/wide/og/pdf-cover) and
 * the real Spatie path generator (Tests/Support/TestSubject/{id}/…).
 */
class TestSubject extends Model implements HasMedia
{
    use HasSewaMedia;
    use HasUlids;

    protected $table = 'test_subjects';

    protected $guarded = [];
}
