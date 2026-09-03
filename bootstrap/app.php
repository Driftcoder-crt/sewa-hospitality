<?php

use App\Console\Commands\LaunchVerify;
use App\Http\Middleware\EnsureAdminSessionFresh;
use App\Http\Middleware\EnsureTwoFactorConfirmed;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetRequestContext;
use App\Modules\Billing\Commands\MarkOverdueInvoices;
use App\Modules\Billing\Commands\SendInvoiceReminders;
use App\Modules\Blog\Commands\PublishScheduledPosts;
use App\Modules\Cms\Commands\GenerateSitemap;
use App\Modules\Cms\Commands\PublishScheduledPages;
use App\Modules\Cms\Commands\SeoAudit;
use App\Modules\I18n\Http\Middleware\LocaleResolver;
use App\Modules\Leads\Commands\CalculateSla;
use App\Modules\Portal\Commands\PortalHousekeeping;
use App\Modules\Testimonials\Commands\SyncGoogleReviews;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/*
|--------------------------------------------------------------------------
| SEWA HOSPITALITY — Application bootstrap
|--------------------------------------------------------------------------
| One Laravel 13 modular monolith serving four hosts from one public/:
|   sewahospitality.com  → routes/web.php      (public site)
|   admin.sewa…          → routes/admin.php    (custom admin panel)
|   app.sewa…            → routes/portal.php   (client portal)
|   api.sewa…            → routes/api.php      (versioned REST /v1)
| media.sewa… is static-only (storage symlink + .htaccess immutable cache).
|
| Spec: 03-technical-specs/02-architecture.md §4 (subdomain routing map).
| Staging/local collapse the host constraint so every area is reachable
| on one host; production/staging enforce the real domains.
*/

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands([
        // Module commands (auto-discovery covers app/Console only).
        PublishScheduledPages::class,
        GenerateSitemap::class,
        SeoAudit::class,
        CalculateSla::class,
        PublishScheduledPosts::class,
        SyncGoogleReviews::class,
        PortalHousekeeping::class,
        MarkOverdueInvoices::class,
        SendInvoiceReminders::class,
        LaunchVerify::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            $constrain = app()->environment('staging', 'production');

            $domain = function (string $key) use ($constrain): ?string {
                return $constrain ? (config("sewa.domains.{$key}") ?: null) : null;
            };

            // Local/test environments have no per-host domains, so all three
            // surfaces would register '/' on the same domain and Laravel's
            // RouteCollection (keyed domain+uri+method) silently keeps only
            // the LAST one — admin.dashboard and the web home vanished.
            // Fall back to path prefixes locally; production routes by host
            // per 02-architecture §4. API always rides /v1 (no collision).
            $prefix = fn (string $key): string => $constrain ? '' : $key;

            Route::middleware('web')
                ->domain($domain('admin'))
                ->prefix($prefix('admin'))
                ->group(__DIR__.'/../routes/admin.php');

            Route::middleware('web')
                ->domain($domain('app'))
                ->prefix($prefix('portal'))
                ->group(__DIR__.'/../routes/portal.php');

            Route::middleware('api')
                ->domain($domain('api'))
                ->prefix('v1')
                ->group(__DIR__.'/../routes/api.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cloudflare sits in front of everything — real client IPs for
        // rate limiting + audit (05-security-reliability §1.3).
        $proxies = array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))));
        if ($proxies !== []) {
            $middleware->trustProxies(at: $proxies);
        }

        // Area context must run before the session starts (per-area cookie
        // names); locale resolution runs after the session (cookie +
        // banner state) and before security headers wrap the response.
        $middleware->web(
            prepend: [SetRequestContext::class],
            append: [
                LocaleResolver::class,
                SecurityHeaders::class,
            ],
        );

        // Public-facing preference cookies stay UNENCRYPTED: the consent
        // banner writes sewa_consent via raw document.cookie (Alpine) —
        // an encrypted-read would null it and the visitor's explicit
        // choice would never reach the server. sewa_locale follows the
        // same policy (values: 'all'|'essential'|'analytics', a locale
        // code — nothing sensitive either way; DPDP: preferences only).
        $middleware->encryptCookies(except: [
            \App\Support\Analytics\Consent::COOKIE,
            \App\Modules\I18n\Http\Middleware\LocaleResolver::COOKIE,
        ]);

        $middleware->alias([
            'admin.fresh-session' => EnsureAdminSessionFresh::class,
            'admin.2fa' => EnsureTwoFactorConfirmed::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API envelope (03-technical-specs/04-api-spec.md):
        // success {data, meta} · error {error: {code, message, details}}.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! ($request->is('v1/*') || $request->is('api/v1/*'))) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface
                ? $e->getStatusCode()
                : (int) ($e->getCode() ?: 500);

            if ($status < 400 || $status > 599) {
                $status = 500;
            }

            [$code, $message, $details] = match (true) {
                $e instanceof ValidationException => ['validation_failed', 'The given data was invalid.', $e->errors()],
                $e instanceof ThrottleRequestsException => ['rate_limited', 'Too many requests. Please retry shortly.', ['retry_after' => method_exists($e, 'getHeaders') ? ($e->getHeaders()['Retry-After'] ?? null) : null]],
                $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => ['not_found', 'The requested resource was not found.', null],
                $e instanceof AuthenticationException => ['unauthenticated', 'Authentication is required.', null],
                $e instanceof AuthorizationException => ['forbidden', 'You are not allowed to perform this action.', null],
                $e instanceof TokenMismatchException => ['csrf_mismatch', 'Your session expired. Please retry.', null],
                default => ['server_error', app()->environment('production') ? 'An unexpected error occurred.' : $e->getMessage(), null],
            };

            return response()->json(
                ['error' => ['code' => $code, 'message' => $message, 'details' => $details]],
                $status,
            );
        });
    })
    ->create();
