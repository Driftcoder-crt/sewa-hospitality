<?php

use Sentry\Event;

/*
|--------------------------------------------------------------------------
| Sentry configuration (compact)
|--------------------------------------------------------------------------
| Error tracking per 03-technical-specs/12-monitoring.md §2: release is the
| deployed git SHA, performance sampling at 10%, and PII never leaves the
| box — `before_send` below scrubs request payloads before transport.
| Without SENTRY_LARAVEL_DSN the client is inert (no-op transport).
*/

return [

    'dsn' => env('SENTRY_LARAVEL_DSN'),

    // Set by the deploy runbook: SENTRY_RELEASE=<git-sha> (06-hosting §7).
    'release' => env('SENTRY_RELEASE'),

    'environment' => env('APP_ENV'),

    // 10% performance tracing (12-monitoring §2).
    'traces_sample_rate' => (float) env('SENTRY_TRACES_RATE', 0.1),

    'send_default_pii' => false,

    /*
     | PII scrubbing (05-security-reliability §1.4): recurse the parsed
     | request body and replace known PII keys — credentials, contact
     | details, free-text messages and any token/secret — with a
     | '[redacted]' marker. Only request `data` is touched; the event
     | itself (stack traces, fingerprint) passes through untouched.
     */
    'before_send' => static function (Event $event): ?Event {
        $redact = static function (array $data) use (&$redact): array {
            $sensitive = ['password', 'password_confirmation', 'email', 'phone', 'message', 'token', 'secret'];

            foreach ($data as $key => $value) {
                if (in_array(strtolower((string) $key), $sensitive, true)) {
                    $data[$key] = '[redacted]';
                } elseif (is_array($value)) {
                    $data[$key] = $redact($value);
                }
            }

            return $data;
        };

        $request = $event->getRequest();

        if (is_array($request) && isset($request['data']) && is_array($request['data'])) {
            $request['data'] = $redact($request['data']);
            $event->setRequest($request);
        }

        return $event;
    },

];
