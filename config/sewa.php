<?php

/*
|--------------------------------------------------------------------------
| SEWA platform configuration
|--------------------------------------------------------------------------
| Domain architecture (01-platform-vision/04-subdomains-ventures.md):
| all subdomains resolve to the same public/; routing distinguishes by
| host. Values are env-driven so local/staging/production can differ.
*/

return [

    'domains' => [
        'site' => env('SEWA_DOMAIN_SITE', 'sewahospitality.com'),
        'admin' => env('SEWA_DOMAIN_ADMIN', 'admin.sewahospitality.com'),
        'app' => env('SEWA_DOMAIN_APP', 'app.sewahospitality.com'),
        'api' => env('SEWA_DOMAIN_API', 'api.sewahospitality.com'),
        'media' => env('SEWA_DOMAIN_MEDIA', 'media.sewahospitality.com'),
    ],

    /*
     | Staff roles that may enter the admin surface (04-modules/05-admin-panel.md §5).
     | Portal roles (client-manager, client-employee) never appear here.
     */
    'staff_roles' => [
        'super-admin', 'admin', 'editor', 'author',
        'hr-manager', 'recruiter', 'finance', 'consultant', 'ops',
    ],

    /*
     | Admin session policy (03-technical-specs/05-security-reliability.md §1.1):
     | 2h idle timeout, 2FA mandatory for super-admin/admin.
     */
    'admin' => [
        'idle_timeout_minutes' => (int) env('SEWA_ADMIN_IDLE_MINUTES', 120),
        'two_factor_roles' => ['super-admin', 'admin'],
    ],

    /*
     | /dev/components visual gallery (05-design-system/02-ui-components
     | §3): always available in local; elsewhere opt-in via env — it is
     | noindexed regardless.
     */
    'dev_routes' => (bool) env('SEWA_ENABLE_DEV_ROUTES', false),

    /*
     | From-address identities (03-technical-specs/10-email.md §2) — one
     | provider domain config. `ops` is the alert/digest list (10-email
     | §4 ops.digest; comma-separated env).
     */
    'emails' => [
        'hello' => env('SEWA_EMAIL_HELLO', 'hello@sewahospitality.com'),
        'support' => env('SEWA_EMAIL_SUPPORT', 'support@sewahospitality.com'),
        'careers' => env('SEWA_EMAIL_CAREERS', 'careers@sewahospitality.com'),
        'no_reply' => env('SEWA_EMAIL_NOREPLY', 'no-reply@sewahospitality.com'),
        'billing' => env('SEWA_EMAIL_BILLING', 'billing@sewahospitality.com'),
        'ops' => array_values(array_filter(array_map('trim', explode(',', (string) env('SEWA_OPS_EMAILS', 'hello@sewahospitality.com'))))),
    ],

    /*
     | Privacy consent version stamped next to consent_at on every public
     | form (privacy error lock #5: consent_at + policy version).
     */
    'privacy_version' => env('SEWA_PRIVACY_VERSION', '2026-01'),

    /*
     | Lead intake knobs (04-modules/03-leads-crm.md): dedupe window and
     | the unassigned-escalation threshold. SLA windows live in
     | SlaPolicy (business-hours aware) — one source of truth.
     */
    'leads' => [
        'dedupe_hours' => 48,
        'escalate_unassigned_minutes' => 15,
    ],

    /*
     | Public-write guard for lead/application forms (05-security-
     | reliability §1.2): 5/min/IP (AppServiceProvider limiter) layered
     | with 20/h/IP here.
     */
    'forms' => [
        'per_minute' => 5,
        'per_hour' => 20,
        'min_seconds' => 2, // time-trap: faster than this is a bot
    ],

    /*
     | Media namespaces (03-technical-specs/09-media-pipeline.md §2).
     */
    'media' => [
        'namespaces' => [
            'brand', 'services', 'cities', 'housing', 'blog', 'team',
            'csr', 'testimonials', 'careers', 'portal', 'legal',
        ],
        'max_image_bytes' => (int) env('SEWA_MEDIA_MAX_BYTES', 8 * 1024 * 1024), // images ≤ 8 MB
        'max_resume_bytes' => 5 * 1024 * 1024, // PDF/DOC resume ≤ 5 MB (careers only)
    ],

    /*
     | Nightly backups live OUTSIDE the app dir on the host
     | (/home/uXXXX/backups — 06-hosting-deployment §8). Local default
     | keeps them in storage so nothing escapes the project root.
     */
    'backups_path' => env('SEWA_BACKUPS_PATH', storage_path('app/backups')),

    /*
     | Cloudflare Turnstile (error lock #3). `fail_mode` governs the
     | circuit-breaker-open behaviour: "grace" keeps user flows alive
     | (honeypot still enforced, ops alerted); "strict" fails closed.
     */
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret' => env('TURNSTILE_SECRET_KEY'),
        'fail_mode' => env('TURNSTILE_FAIL_MODE', 'grace'), // grace|strict
        'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    ],

    /*
     | Analytics (07-marketing-trust/02-analytics-plan.md): consent-first
     | GA4/GTM — no tag fires before an explicit banner choice, and
     | server conversions go through the Measurement Protocol (PII-free).
     | Empty ids = analytics fully off (default at launch).
     */
    'analytics' => [
        'ga4_id' => env('GA4_MEASUREMENT_ID'),
        'gtm_id' => env('GTM_CONTAINER_ID'),
        'ga4_api_secret' => env('GA4_API_SECRET'),
        'mp_endpoint' => 'https://www.google-analytics.com/mp/collect',
    ],

];
