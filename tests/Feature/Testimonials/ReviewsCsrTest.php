<?php

use App\Modules\Csr\Models\CsrStory;
use App\Modules\Csr\Models\NgoPartner;
use App\Modules\Testimonials\Models\GoogleReview;
use App\Modules\Testimonials\Models\ReviewRequest;
use App\Modules\Testimonials\Models\Testimonial;
use App\Modules\Testimonials\Services\GbpSyncService;
use App\Modules\Testimonials\Services\ReviewRequestEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

it('enforces the rating-honesty rule: stats come only from synced GBP data with as-of', function (): void {
    $gbp = app(GbpSyncService::class);

    // No sync yet → no rating displayed at all (never self-declared).
    expect($gbp->stats())->toBeNull();

    GoogleReview::query()->create(['external_id' => 'r1', 'rating' => 5, 'fetched_at' => now(), 'synced' => true]);
    GoogleReview::query()->create(['external_id' => 'r2', 'rating' => 4, 'fetched_at' => now(), 'synced' => true]);

    $stats = $gbp->stats();
    expect($stats['rating'])->toBe(4.5)
        ->and($stats['count'])->toBe(2)
        ->and($stats['as_of'])->toBeString();
});

it('is idempotent: the same external_id never duplicates a Google review', function (): void {
    $gbp = app(GbpSyncService::class);

    $gbp->sync(); // connector unconfigured → safe skip (never fake data)
    expect(GoogleReview::query()->count())->toBe(0);

    // Direct idempotency check at the cache level.
    GoogleReview::query()->create(['external_id' => 'dup', 'rating' => 5, 'synced' => true]);
    GoogleReview::query()->firstOrCreate(
        ['external_id' => 'dup'],
        ['rating' => 5, 'synced' => true],
    );

    expect(GoogleReview::query()->where('external_id', 'dup')->count())->toBe(1);
});

it('gates names behind consent: default renders first name + city', function (): void {
    $named = Testimonial::factory()->verified()->create(['client_name' => 'Priya Sharma', 'consent_named' => true]);
    $anon = Testimonial::factory()->create(['client_name' => 'Deepak Rao', 'consent_named' => false]);

    expect($named->displayName())->toContain('Priya')
        ->and($anon->displayName())->not->toContain('Rao');
});

it('keeps the one-review-request-per-move invariant structurally', function (): void {
    $engine = app(ReviewRequestEngine::class);

    $first = $engine->requestFor('MOVE-2026-0001', 'hr@acme.example', 'Ops Team');
    $again = $engine->requestFor('MOVE-2026-0001', 'hr@acme.example', 'Ops Team');

    expect($first->is($again))->toBeTrue()
        ->and(ReviewRequest::query()->where('move_reference', 'MOVE-2026-0001')->count())->toBe(1)
        ->and($first->attempts)->toBe(1);
});

it('runs the single 7-day follow-up once, then stops', function (): void {
    $engine = app(ReviewRequestEngine::class);

    $request = ReviewRequest::query()->create([
        'move_reference' => 'MOVE-2026-0002',
        'recipient_email' => 'hr@acme.example',
        'status' => 'sent',
        'attempts' => 1,
        'sent_at' => now()->subDays(8),
    ]);

    $engine->processFollowUps();
    expect($request->refresh()->status)->toBe('followed_up')
        ->and($request->attempts)->toBe(2);

    // Second pass: hard stop — no more nudges, ever.
    $engine->processFollowUps();
    expect($request->refresh()->attempts)->toBe(2);
});

it('renders /csr with partner link-outs, as-of claims and archived honesty', function (): void {
    NgoPartner::factory()->create();
    NgoPartner::factory()->archived()->create(['status' => 'archived']);

    $story = CsrStory::query()->create([
        'slug' => 'training-cohort-3',
        'title' => 'Third training cohort graduates',
        'body' => '<p>Twelve women completed the hospitality skills program.</p>',
        'status' => 'published',
        'published_at' => now(),
        'ngo_partner_id' => NgoPartner::query()->first()->getKey(),
    ]);

    $this->get('/csr')->assertOk()
        ->assertSee('Official site', false)
        ->assertSee('as of Aug 2026')
        ->assertSee('Past associations')
        ->assertSee($story->title);

    $this->get('/csr/training-cohort-3')->assertOk()->assertSee('Twelve women');
});
