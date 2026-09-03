<?php

namespace App\Support\Analytics;

use App\Support\Locks\CircuitBreaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Server-confirmed conversions (02-analytics-plan.md §2: "a lead counts
 * when it's in the DB — server event"; funnel §1.2). GA4 Measurement
 * Protocol, strictly PII-free (§1.1: "IDs and context only" — no
 * emails, names, phones, message text) and consent-checked upstream:
 * the dispatcher skips subjects who never gave consent.
 *
 * Fail-open by contract (05-security-reliability §2.3): breaker 'ga-mp'
 * — an analytics outage never touches a lead.
 */
final class MeasurementProtocol
{
    public const SERVICE = 'ga-mp';

    /**
     * @param  string  $name  GA4 event name (snake_case taxonomy)
     * @param  string  $subjectId  ULID of the DB row (becomes the hashed client_id + transaction_id)
     * @param  array<string, mixed>  $params  non-PII event params only
     */
    public static function track(string $name, string $subjectId, array $params = []): bool
    {
        $measurementId = Consent::ga4Id();
        $apiSecret = (string) config('sewa.analytics.ga4_api_secret', '');

        if ($measurementId === null || $apiSecret === '') {
            return false; // analytics off — never an error, by design
        }

        $clientId = 'server.'.substr(hash('sha256', 'sewa:'.$name.':'.$subjectId), 0, 24);

        $payload = [
            'client_id' => $clientId,
            'non_personalized_ads' => true,
            'events' => [
                [
                    'name' => $name,
                    'params' => array_merge([
                        'transaction_id' => (string) $subjectId,
                        'currency' => 'INR',
                    ], $params),
                ],
            ],
        ];

        try {
            $status = CircuitBreaker::call(
                self::SERVICE,
                function () use ($measurementId, $apiSecret, $payload): bool {
                    $response = Http::timeout(5)
                        ->connectTimeout(3)
                        ->post(config('sewa.analytics.mp_endpoint').'?measurement_id='.$measurementId.'&api_secret='.$apiSecret, $payload);

                    if ($response->failed()) {
                        throw new \RuntimeException('GA4 MP HTTP '.$response->status());
                    }

                    return true;
                },
                fallback: fn (): bool => false, // breaker open → skip silently
            );
        } catch (\Throwable $e) {
            Log::channel('ops')->info('GA4 Measurement Protocol call failed', [
                'event' => $name,
                'exception' => $e::class,
            ]);

            return false;
        }

        return (bool) $status;
    }
}
