<?php

namespace App\Modules\Testimonials\Services;

use App\Modules\Leads\Mail\OpsAlertMail;
use App\Modules\Testimonials\Models\GoogleReview;
use App\Modules\Testimonials\Models\Testimonial;
use App\Support\Locks\CircuitBreaker;
use App\Support\Mail\Jobs\SendTemplateMail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GBP sync (08 doc §4.2/§5): daily 06:00 pull of Google reviews into
 * the idempotent cache (external_id unique) + stats snapshot. ≤3★
 * reviews trigger the service-recovery alert (SLA 4h outreach). The
 * GBP API sits behind the `gbp` breaker; on failure the site keeps
 * last-known stats WITH the "synced {date}" note (never stale-unlabeled).
 */
final class GbpSyncService
{
    /**
     * Pull reviews from the configured provider (or API placeholder —
     * the GBP connector keys live in config/services.php 'google' and
     * are provisioned at M6 marketing wiring; until then the sync is a
     * safe no-op that keeps the schedule honest).
     *
     * @return array{imported: int, recovered: int, skipped: bool}
     */
    public function sync(): array
    {
        $apiKey = (string) config('services.google.places_api_key', '');
        $placeId = (string) config('services.google.gbp_place_id', '');

        if ($apiKey === '' || $placeId === '') {
            Log::channel('ops')->info('GBP sync skipped — connector not configured yet (M6 wiring)');

            return ['imported' => 0, 'recovered' => 0, 'skipped' => true];
        }

        $result = CircuitBreaker::call(
            service: 'gbp',
            task: fn (): array => $this->pull($apiKey, $placeId),
            fallback: fn (): array => ['imported' => 0, 'recovered' => 0, 'skipped' => true],
        );

        return $result;
    }

    private function pull(string $apiKey, string $placeId): array
    {
        $response = Http::timeout(10)->get(
            'https://places.googleapis.com/v1/places/'.$placeId,
            [
                'key' => $apiKey,
                'fields' => 'rating,userRatingCount,reviews',
            ],
        );

        $payload = $response->json();
        $imported = 0;
        $recovered = 0;

        foreach ((array) ($payload['reviews'] ?? []) as $review) {
            $externalId = (string) ($review['name'] ?? '');

            if ($externalId === '') {
                continue;
            }

            $model = GoogleReview::query()->firstOrNew(['external_id' => $externalId]);

            if ($model->exists) {
                continue; // idempotent: double-cron never duplicates
            }

            $model->fill([
                'rating' => (int) ($review['rating'] ?? 0),
                'text' => $review['text']['text'] ?? null,
                'reviewer' => $review['authorAttribution']['displayName'] ?? null,
                'review_at' => isset($review['createTime']) ? Carbon::parse($review['createTime']) : null,
                'url' => $review['googleMapsUri'] ?? null,
                'fetched_at' => now(),
                'synced' => true,
            ])->save();

            // Mirror into the curated table as a published Google testimonial.
            Testimonial::query()->firstOrCreate(
                ['google_review_id' => $model->getKey()],
                [
                    'client_name' => $model->reviewer ?? 'Google client',
                    'body' => (string) $model->text,
                    'rating' => $model->rating,
                    'source' => 'google',
                    'source_url' => $model->url,
                    'status' => 'published',
                    'published_at' => now(),
                    'verified_at' => now(),
                    'consent_named' => true, // published on Google already
                ],
            );

            $imported++;

            // Service recovery loop (08 doc §5): ≤3★ = immediate ops alert.
            if ($model->rating <= 3) {
                $recovered++;

                SendTemplateMail::dispatch(
                    key: "gbp.recovery:{$model->getKey()}",
                    template: 'gbp.recovery',
                    mailable: new OpsAlertMail(
                        alertSubject: 'Service recovery needed — low Google rating',
                        lines: [
                            "Review: {$model->rating}★ from {$model->reviewer}",
                            str($model->text ?? '')->limit(200)->toString(),
                        ],
                        linkUrl: $model->url,
                        linkLabel: 'Open on Google Maps',
                    ),
                );
            }
        }

        return ['imported' => $imported, 'recovered' => $recovered, 'skipped' => false];
    }

    /** Live GBP stats for the honest rating display (rating-honesty rule). */
    public function stats(): ?array
    {
        $reviews = GoogleReview::query();

        if ($reviews->count() === 0) {
            return null;
        }

        // Builder aggregates skip model casts — max() hands back the raw
        // column string, so wrap it in Carbon before formatting.
        $asOf = $reviews->max('fetched_at');

        return [
            'rating' => round((float) $reviews->avg('rating'), 1),
            'count' => (int) $reviews->count(),
            'as_of' => $asOf ? Carbon::parse($asOf)->format('M Y') : now()->format('M Y'),
        ];
    }
}
