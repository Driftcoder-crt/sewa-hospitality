<?php

namespace Database\Seeders;

use App\Modules\Cms\Enums\MenuItemType;
use App\Modules\Cms\Enums\MenuLocation;
use App\Modules\Cms\Enums\PageStatus;
use App\Modules\Cms\Enums\PageType;
use App\Modules\Cms\Models\Menu;
use App\Modules\Cms\Models\MenuItem;
use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Services\MenuService;
use Illuminate\Database\Seeder;

/**
 * CMS seed (04-modules/01-cms.md §2/§3 + 06-content-seo/06-copy-
 * templates.md): the launch core pages composed from wave-1 blocks,
 * plus header/footer menus. Copy is honest launch content — stats
 * blocks are NOT seeded (no invented numbers; D3 lands with real,
 * dated values in M4). NAP never appears retyped — contact copy uses
 * the settings-sourced identity at render time.
 */
class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            'home' => [
                'title' => 'Sewa Hospitality — Corporate relocation & global mobility in India',
                'type' => PageType::Standard,
                'meta_title' => 'Corporate Relocation & Global Mobility in India',
                'meta_description' => 'Employee mobility, immigration, corporate housing and moving services across India — one accountable partner for teams, families and the people who move them.',
                'blocks' => [
                    ['type' => 'hero', 'data' => [
                        'eyebrow' => 'SEWA HOSPITALITY',
                        'headline' => 'Care, delivered.',
                        'sub' => 'Corporate relocation, global mobility and hospitality services across India — for teams, families and the people who move them.',
                        'height' => 'full',
                        'overlay' => 'none',
                        'align' => 'start',
                        'ctas' => [
                            ['label' => 'Talk to a consultant', 'url' => '/contact', 'variant' => 'primary'],
                            ['label' => 'Call +91 98732 55531', 'url' => 'tel:+919873255531', 'variant' => 'secondary'],
                        ],
                    ]],
                    ['type' => 'rich_text', 'data' => [
                        'html' => '<h2>Who we are</h2><p>SEWA HOSPITALITY SERVICES PVT. LTD. is a Gurugram-headquartered mobility partner covering every stage of a move: <strong>employee mobility</strong> (relocation, serviced apartments, moving, corporate housing, fleet), <strong>immigration</strong> (inbound, outbound and ancillary services) and <strong>business mobility</strong> (travel, business space, recruitment, interiors, sanitization).</p><p>One accountable team, one point of contact, and a service standard we call <em>Sewa Verified</em> — what we promise in writing is what we deliver on site.</p>',
                    ]],
                    ['type' => 'feature_grid', 'data' => [
                        'columns' => '3',
                        'style' => 'border',
                        'items' => [
                            ['title' => 'Global mobility', 'text' => 'Immigration, relocation and settling-in support for international teams.', 'icon' => 'globe', 'url' => ''],
                            ['title' => 'Corporate housing', 'text' => 'Serviced apartments and managed stays, verified by our own standard.', 'icon' => 'building', 'url' => ''],
                            ['title' => 'Cities & coverage', 'text' => 'An all-India city program with honest, dated local information.', 'icon' => 'map', 'url' => ''],
                        ],
                    ]],
                    ['type' => 'accordion', 'data' => [
                        'first_open' => true,
                        'items' => [
                            ['title' => 'How we work — one plan, one owner', 'body_html' => '<p>Every move starts with a scoped plan and a named consultant who owns it end to end. You always know who is accountable, and what happens next.</p>'],
                            ['title' => 'Honest, dated information', 'body_html' => '<p>Rates, city notes and availability are published with an "as of" date. When something changes, the page changes — no stale promises.</p>'],
                            ['title' => 'Care, delivered.', 'body_html' => '<p>From airport pickup to lease signing to the first grocery run, our teams handle the details so the people we move can focus on their work and families.</p>'],
                        ],
                    ]],
                    ['type' => 'cta_band', 'data' => [
                        'headline' => 'Ready to plan your move?',
                        'copy' => 'Tell us who is moving and where — a consultant replies with a scoped plan.',
                        'theme' => 'brand',
                        'layout' => 'centered',
                        'ctas' => [['label' => 'Talk to a consultant', 'url' => '/contact', 'variant' => 'primary']],
                    ]],
                ],
            ],
            'about' => [
                'title' => 'About Sewa Hospitality',
                'type' => PageType::About,
                'meta_title' => 'About Sewa Hospitality',
                'meta_description' => 'Who we are: a Gurugram-headquartered hospitality and mobility company built on one promise — care, delivered.',
                'blocks' => [
                    ['type' => 'hero', 'data' => [
                        'eyebrow' => 'ABOUT US',
                        'headline' => 'A mobility partner, not a vendor list.',
                        'sub' => 'Sewa means service. We built the company around the idea that a move handled with care is a move that succeeds.',
                        'height' => 'compact',
                        'overlay' => 'none',
                        'align' => 'start',
                        'ctas' => [],
                    ]],
                    ['type' => 'text_media', 'data' => [
                        'title' => 'Founded on service',
                        'copy' => '<p>We serve five audiences: corporate mobility teams, HR leaders, individual relocators, housing partners and our own people. Everything we publish — city notes, rates, availability — carries an "as of" date, because trust is built on honest, current information.</p>',
                        'media_side' => 'right',
                    ]],
                    ['type' => 'chapter_heading', 'data' => [
                        'number' => '01',
                        'title' => 'What we stand for',
                    ]],
                    ['type' => 'feature_grid', 'data' => [
                        'columns' => '3',
                        'style' => 'plain',
                        'items' => [
                            ['title' => 'Accountable', 'text' => 'A named owner for every move and every ticket.', 'icon' => 'user-check', 'url' => ''],
                            ['title' => 'Transparent', 'text' => 'Written scopes, dated rates, no surprise line items.', 'icon' => 'file-text', 'url' => ''],
                            ['title' => 'Careful', 'text' => 'Verified housing standards and trained field teams.', 'icon' => 'shield-check', 'url' => ''],
                        ],
                    ]],
                    ['type' => 'cta_band', 'data' => [
                        'headline' => 'Meet the team behind the promise',
                        'copy' => 'Questions about how we work? A consultant will walk you through it.',
                        'theme' => 'deep',
                        'layout' => 'split',
                        'ctas' => [['label' => 'Contact us', 'url' => '/contact', 'variant' => 'primary']],
                    ]],
                ],
            ],
            'contact' => [
                'title' => 'Contact Sewa Hospitality',
                'type' => PageType::Standard,
                'meta_title' => 'Contact Sewa Hospitality',
                'meta_description' => 'Reach our Gurugram headquarters: MS0228, 2nd Floor, DT Mega Mall, DLF Phase 1, Gurugram — or call +91 98732 55531.',
                'blocks' => [
                    ['type' => 'hero', 'data' => [
                        'eyebrow' => 'CONTACT',
                        'headline' => 'Talk to a consultant.',
                        'sub' => 'Call, email or visit our Gurugram headquarters — a named consultant answers.',
                        'height' => 'compact',
                        'overlay' => 'none',
                        'align' => 'start',
                        'ctas' => [['label' => 'Call +91 98732 55531', 'url' => 'tel:+919873255531', 'variant' => 'primary']],
                    ]],
                    ['type' => 'rich_text', 'data' => [
                        'html' => '<h2>Headquarters</h2><p><strong>SEWA HOSPITALITY SERVICES PVT. LTD.</strong><br>MS0228, 2nd Floor, DT Mega Mall, A Block, DLF Phase 1,<br>Gurugram, Haryana 122002, India</p><p>Phone: <a href="tel:+919873255531">+91 98732 55531</a><br>Email: <a href="mailto:hello@sewahospitality.com">hello@sewahospitality.com</a></p><p>Support: <a href="mailto:support@sewahospitality.com">support@sewahospitality.com</a> · Careers: <a href="mailto:careers@sewahospitality.com">careers@sewahospitality.com</a></p>',
                    ]],
                    ['type' => 'lead_form', 'data' => [
                        'form_type' => 'contact',
                        'heading' => 'Send us a message',
                        'intro' => 'Tell us who is moving, where and when — a named consultant replies with a plan.',
                        'benefits' => [
                            ['text' => 'Reply within 2 business hours'],
                            ['text' => 'A named consultant — never a ticket queue'],
                            ['text' => 'Honest advice even when it costs us the sale'],
                        ],
                        'privacy_note' => 'We use your details only to answer this enquiry. Read our privacy policy for the full picture.',
                    ]],
                    ['type' => 'cta_band', 'data' => [
                        'headline' => 'Prefer we call you?',
                        'copy' => 'Skip the typing — leave your number and a consultant calls back within 2 business hours.',
                        'theme' => 'brand',
                        'layout' => 'centered',
                        'ctas' => [['label' => 'Call now', 'url' => 'tel:+919873255531', 'variant' => 'primary']],
                    ]],
                ],
            ],
            'privacy-policy' => [
                'title' => 'Privacy Policy',
                'type' => PageType::Legal,
                'meta_title' => 'Privacy Policy',
                'meta_description' => 'How Sewa Hospitality collects, uses and protects personal data across its services and platforms.',
                'blocks' => [
                    ['type' => 'chapter_heading', 'data' => ['number' => '§', 'title' => 'Privacy Policy']],
                    ['type' => 'rich_text', 'data' => [
                        'html' => '<p><em>Launch-baseline policy. The full legal text is reviewed by counsel before public launch (M7) — this placeholder states the commitments we already operate under.</em></p><h2>What we collect</h2><p>Contact details and move requirements you share with us; nothing else is collected without a lawful basis. We do not send personal data to AI providers.</p><h2>How we use it</h2><p>To answer enquiries, deliver services you request, and keep records our business and Indian law require.</p><h2>Your rights</h2><p>Write to hello@sewahospitality.com to access, correct or delete your data.</p>',
                    ]],
                ],
            ],
            'cookie-policy' => [
                'title' => 'Cookie Policy',
                'type' => PageType::Legal,
                'meta_title' => 'Cookie Policy',
                'meta_description' => 'Cookies and tracking on sewahospitality.com: what runs, what never runs without consent, and how to opt out.',
                'blocks' => [
                    ['type' => 'chapter_heading', 'data' => ['number' => '§', 'title' => 'Cookie Policy']],
                    ['type' => 'rich_text', 'data' => [
                        'html' => '<h2>Strictly necessary</h2><p>Session and security cookies (including the Cloudflare Turnstile challenge) keep the site working — no consent banner can switch these off.</p><h2>Analytics (consent-gated)</h2><p>Analytics tools load only after you opt in via the cookie banner (landed with GA4 in M6). Before consent, zero third-party scripts run on public pages.</p>',
                    ]],
                ],
            ],
        ];

        $adminId = null;

        foreach ($pages as $slug => $spec) {
            Page::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $spec['title'],
                    'type' => $spec['type'],
                    'template' => 'default',
                    'status' => PageStatus::Published,
                    'published_at' => now(),
                    'meta_title' => $spec['meta_title'],
                    'meta_description' => $spec['meta_description'],
                    'blocks' => $spec['blocks'],
                    'locale' => 'en',
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ],
            );
        }

        // ---- Menus (header + footer) ---------------------------------
        $bySlug = Page::query()->whereIn('slug', array_keys($pages))->get()->keyBy('slug');

        $header = Menu::query()->updateOrCreate(
            ['location' => MenuLocation::Header->value],
            ['name' => 'Header navigation', 'locale' => 'en'],
        );
        $header->items()->delete();
        $this->items($header, [
            ['Home', MenuItemType::Page, $bySlug['home']->getKey(), '/'],
            ['About', MenuItemType::Page, $bySlug['about']->getKey(), '/about'],
            ['Contact', MenuItemType::Page, $bySlug['contact']->getKey(), '/contact'],
        ]);
        MenuService::flush();

        $footer = Menu::query()->updateOrCreate(
            ['location' => MenuLocation::Footer->value],
            ['name' => 'Footer columns', 'locale' => 'en'],
        );
        $footer->items()->delete();
        $footerId = $footer->getKey();
        $company = MenuItem::query()->create([
            'menu_id' => $footerId, 'label' => 'Company', 'type' => 'custom',
            'url' => '#', 'sort' => 0,
        ]);
        foreach ([
            ['About', MenuItemType::Page, $bySlug['about']->getKey(), '/about'],
            ['Contact', MenuItemType::Page, $bySlug['contact']->getKey(), '/contact'],
        ] as $i => [$label, $type, $refId, $url]) {
            MenuItem::query()->create([
                'menu_id' => $footerId, 'parent_id' => $company->getKey(), 'label' => $label,
                'type' => $type->value, 'ref_id' => $refId, 'url' => $url, 'sort' => $i,
            ]);
        }
        $legal = MenuItem::query()->create([
            'menu_id' => $footerId, 'label' => 'Legal', 'type' => 'custom',
            'url' => '#', 'sort' => 1,
        ]);
        foreach ([
            ['Privacy Policy', MenuItemType::Page, $bySlug['privacy-policy']->getKey(), '/legal/privacy-policy'],
            ['Cookie Policy', MenuItemType::Page, $bySlug['cookie-policy']->getKey(), '/legal/cookie-policy'],
        ] as $i => [$label, $type, $refId, $url]) {
            MenuItem::query()->create([
                'menu_id' => $footerId, 'parent_id' => $legal->getKey(), 'label' => $label,
                'type' => $type->value, 'ref_id' => $refId, 'url' => $url, 'sort' => $i,
            ]);
        }
        MenuService::flush();
    }

    private function items(Menu $menu, array $items): void
    {
        foreach ($items as $sort => [$label, $type, $refId, $url]) {
            MenuItem::query()->create([
                'menu_id' => $menu->getKey(),
                'label' => $label,
                'type' => $type->value,
                'ref_id' => $refId,
                'url' => $url,
                'sort' => $sort,
            ]);
        }
    }
}
