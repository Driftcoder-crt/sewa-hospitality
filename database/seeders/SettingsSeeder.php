<?php

namespace Database\Seeders;

use App\Modules\Cms\Models\Setting;
use App\Modules\Cms\Services\SettingsRepository;
use Illuminate\Database\Seeder;

/**
 * Launch settings (03-technical-specs/03-database-schema.md §2 + §13).
 * Every value is a real, audited brand value — nothing is invented:
 * socials, memberships and counters start EMPTY until they are actually
 * earned/published (07-marketing-trust/03-trust-authority.md), and
 * integration ids stay null until their milestone wires them up.
 *
 * Idempotent: updateOrCreate on the unique `key`, so re-running
 * refreshes values without duplicating rows. group values follow the
 * CMS settings groups (04-modules/01-cms.md): brand|contact|seo|
 * integrations|legal.
 */
final class SettingsSeeder extends Seeder
{
    private const SETTINGS = [
        [
            'key' => 'organization.identity',
            'group' => 'brand',
            'editable_by_role' => null,
            // Brand JSON — 01-platform-vision/02-brand-sewa-hospitality.md §9,
            // byte-exact. `logo` stays null until a real wordmark asset is
            // uploaded (never a placeholder URL); phone carries BOTH locked
            // formats: display "+91 98732 55531" everywhere human-visible,
            // E.164 "+919873255531" for schema.org JSON and tel: links (NAP rule).
            'value' => [
                'legalName' => 'SEWA HOSPITALITY SERVICES PVT. LTD.',
                'brand' => 'Sewa Hospitality',
                'url' => 'https://sewahospitality.com',
                'logo' => null,
                'telephone' => '+91 98732 55531',
                'telephoneE164' => '+919873255531',
                'email' => 'hello@sewahospitality.com',
                'address' => [
                    'street' => 'MS0228, 2nd Floor, DT Mega Mall, A Block, DLF Phase 1',
                    'city' => 'Gurugram',
                    'state' => 'Haryana',
                    'postalCode' => '122002',
                    'country' => 'IN',
                ],
                'geo' => ['lat' => 28.4949, 'lng' => 77.0886],
                // Real social/business profiles only, added at launch —
                // never aspirational or placeholder URLs.
                'sameAs' => [],
                'slogan' => 'Care, delivered.',
                'foundingDate' => '2026',
            ],
        ],
        [
            'key' => 'contact.nap',
            'group' => 'contact',
            'editable_by_role' => null,
            // NAP must be byte-identical everywhere (single source):
            // templates, GBP, emails and schema markup all render from
            // this row — the values are never re-typed elsewhere.
            'value' => [
                'legalName' => 'SEWA HOSPITALITY SERVICES PVT. LTD.',
                'street' => 'MS0228, 2nd Floor, DT Mega Mall, A Block, DLF Phase 1',
                'city' => 'Gurugram',
                'state' => 'Haryana',
                'postalCode' => '122002',
                'country' => 'IN',
                'phoneDisplay' => '+91 98732 55531',
                'phoneE164' => '+919873255531',
                'email' => 'hello@sewahospitality.com',
            ],
        ],
        [
            'key' => 'contact.emails',
            'group' => 'contact',
            'editable_by_role' => null,
            // From-identities (01-platform-vision/04-subdomains-ventures.md §5,
            // 03-technical-specs/10-email.md §1): hello public replies,
            // support portal, careers applications, no-reply transactional,
            // billing finance.
            'value' => [
                'hello' => 'hello@sewahospitality.com',
                'support' => 'support@sewahospitality.com',
                'careers' => 'careers@sewahospitality.com',
                'noReply' => 'no-reply@sewahospitality.com',
                'billing' => 'billing@sewahospitality.com',
            ],
        ],
        [
            'key' => 'contact.offices',
            'group' => 'contact',
            'editable_by_role' => null,
            // Gurugram HQ only at seed time; further offices are phased
            // adds through the admin (schema §13 "phased adds").
            'value' => [
                [
                    'id' => 'hq-gurugram',
                    'label' => 'HQ — Gurugram',
                    'street' => 'MS0228, 2nd Floor, DT Mega Mall, A Block, DLF Phase 1',
                    'city' => 'Gurugram',
                    'state' => 'Haryana',
                    'postalCode' => '122002',
                    'country' => 'IN',
                    'phoneDisplay' => '+91 98732 55531',
                    'geo' => ['lat' => 28.4949, 'lng' => 77.0886],
                    'primary' => true,
                ],
            ],
        ],
        [
            'key' => 'brand.socials',
            'group' => 'brand',
            'editable_by_role' => null,
            // No invented social links — each stays null until the real
            // profile exists (brand doc §8: handles confirmed at design phase).
            'value' => [
                'linkedin' => null,
                'instagram' => null,
                'facebook' => null,
                'youtube' => null,
                'x' => null,
            ],
        ],
        [
            'key' => 'brand.memberships',
            'group' => 'brand',
            'editable_by_role' => null,
            // Starts EMPTY by hard rule (07-marketing-trust/03-trust-authority.md):
            // badges only ever reflect memberships actually HELD; the
            // EuRA/WERC/IAM roadmap lives in the docs, never on the site.
            'value' => [],
        ],
        [
            'key' => 'platform.counters',
            'group' => 'brand',
            'editable_by_role' => null,
            // Honest stats with an "as of" marker arrive via CMS stats
            // blocks in M2 — nothing is invented at seed time.
            'value' => [],
        ],
        [
            'key' => 'seo.defaults',
            'group' => 'seo',
            'editable_by_role' => 'super-admin',
            // Analytics/GSC ids stay empty until the M6 analytics milestone;
            // never reuse the reference site's ids (02-formula-reference/
            // 05-seo-content-analysis.md).
            'value' => [
                'titleSuffix' => 'Sewa Hospitality',
                'ogImageMediaId' => null,
                'ga4MeasurementId' => null,
                'gtmContainerId' => null,
                'clarityId' => null,
                'gscVerificationMeta' => null,
            ],
        ],
        [
            'key' => 'integrations.turnstile',
            'group' => 'integrations',
            'editable_by_role' => 'super-admin',
            // Turnstile secrets are env-only (error lock #3, config/sewa.php
            // turnstile); this row documents the convention and the
            // dashboard location for the ops team.
            'value' => [
                'note' => 'Keys are env-only (TURNSTILE_SITE_KEY/TURNSTILE_SECRET_KEY), registered per subdomain',
                'dashboard' => 'https://dash.cloudflare.com/?to=/:account/turnstile',
            ],
        ],
        [
            'key' => 'legal.privacy_policy',
            'group' => 'legal',
            'editable_by_role' => null,
            // effectiveAt is set when the legal page is actually published
            // (publish gate, M1) — never backdated.
            'value' => [
                'version' => 'v1.0',
                'effectiveAt' => null,
                'url' => '/legal/privacy',
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::SETTINGS as $setting) {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                    'editable_by_role' => $setting['editable_by_role'],
                ],
            );
        }

        // Seed writes bypass SettingsRepository::set(); flush the
        // read-through cache so the app never serves a stale (empty) set.
        app(SettingsRepository::class)->flush();
    }
}
