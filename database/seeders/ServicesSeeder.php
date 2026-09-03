<?php

namespace Database\Seeders;

use App\Modules\Services\Enums\ServiceFamily;
use App\Modules\Services\Enums\ServiceStatus;
use App\Modules\Services\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Services seed (01-platform-vision/03-service-catalog.md — the source
 * of truth): 11 services across 14 URLs. Slugs, lead_tags and CTA
 * labels are LOCKED to that doc's table (rows 1–14); copy is honest
 * launch baseline. The immigration hub is a child of employee-mobility
 * (its own path per the doc) whose 3 children live at
 * /services/immigration/* — the parent-slug tree produces exactly the
 * catalog's URLs.
 */
class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        $families = [
            'employee-mobility' => [
                'name' => 'Employee mobility',
                'short_desc' => 'Relocation, housing, moving and fleet support for your people arriving in India — or leaving it.',
                'icon_svg_key' => 'plane',
                'intro' => 'Employee mobility at Sewa Hospitality means one accountable team for every stage of an assignment: housing, immigration, moving and the daily logistics that make a posting work.',
                'meta_title' => 'Employee Mobility Services in India',
                'meta_description' => 'Relocation, immigration, serviced apartments, moving, corporate housing and fleet — one accountable employee mobility partner across India.',
                'lead_tag' => 'relocation',
                'cta' => 'Talk to a consultant',
                'icon' => 'plane',
            ],
            'business-mobility' => [
                'name' => 'Business mobility',
                'short_desc' => 'Travel, business space, recruitment, interiors and facilities support for companies operating in India.',
                'icon_svg_key' => 'building',
                'intro' => 'Business mobility covers the operational side of a company footprint: travel desks, workspace, hiring support, interiors and facilities care.',
                'meta_title' => 'Business Mobility Services in India',
                'meta_description' => 'Travel, business space, recruitment, interior design and sanitization services for companies operating in India.',
                'lead_tag' => 'space',
                'cta' => 'Talk to a consultant',
                'icon' => 'building',
            ],
            'immigration' => [
                'name' => 'Immigration',
                'short_desc' => 'Inbound, outbound and ancillary immigration services — registrations, visas and compliance timelines, handled with care.',
                'icon_svg_key' => 'file-text',
                'intro' => 'Our immigration desk handles FRRO registrations, visa applications and the compliance calendar so assignees stay legal and informed — with an accountable owner per case.',
                'meta_title' => 'Immigration Services — Inbound & Outbound India',
                'meta_description' => 'Inbound and outbound immigration, FRRO registration and ancillary services (PAN, driving licences) handled end-to-end.',
                'lead_tag' => 'immigration',
                'cta' => 'Book consultation',
                'icon' => 'file-text',
            ],
        ];

        $ids = [];

        foreach ($families as $slug => $spec) {
            $ids[$slug] = Service::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'family' => $slug === 'immigration' ? ServiceFamily::Standalone->value : $slug,
                    'parent_id' => null,
                    'name' => $spec['name'],
                    'short_desc' => $spec['short_desc'],
                    'intro' => $spec['intro'],
                    'icon_svg_key' => $spec['icon'],
                    'status' => ServiceStatus::Published->value,
                    'sort' => $slug === 'employee-mobility' ? 0 : ($slug === 'business-mobility' ? 1 : 0),
                    'lead_tag' => $spec['lead_tag'],
                    'meta_title' => $spec['meta_title'],
                    'meta_description' => $spec['meta_description'],
                    'content_blocks' => [
                        ['type' => 'accordion', 'data' => [
                            'first_open' => true,
                            'items' => [
                                ['title' => 'How we work', 'body_html' => '<p>A named consultant owns your file end to end — you always know who is accountable and what happens next.</p>'],
                                ['title' => 'Honest, dated information', 'body_html' => '<p>Timelines and requirements are published with an "as of" date and updated when the rules change.</p>'],
                            ],
                        ]],
                        ['type' => 'cta_band', 'data' => [
                            'headline' => 'Ready when you are.',
                            'copy' => 'A consultant replies with a scoped plan and next steps.',
                            'theme' => 'brand',
                            'layout' => 'centered',
                            'ctas' => [['label' => $spec['cta'], 'url' => '/contact', 'variant' => 'primary']],
                        ]],
                    ],
                ],
            )->getKey();
        }

        // Leaves — exact rows 1–14 of the catalog doc (hubs excluded).
        $leaves = [
            ['employee-mobility', 'relocation', 'Relocation', 'Door-to-door relocation for employees and families — packing, shipping, settling-in and ongoing care.', 'relocation', 'Request a move plan', 'home', 0],
            ['employee-mobility', 'immigration', 'Immigration hub', 'Registrations, visas and compliance for inbound and outbound assignees — one desk, one owner.', 'immigration', 'Book consultation', 'file-text', 1],
            ['employee-mobility', 'serviced-apartments', 'Serviced apartments', 'Move-in-ready serviced apartments with housekeeping, utilities and our own verified standard.', 'housing.serviced', 'Check availability', 'building', 2],
            ['employee-mobility', 'moving', 'Moving', 'Household goods moving with careful packing, honest inventories and on-time delivery.', 'moving', 'Get a move quote', 'home', 3],
            ['employee-mobility', 'corporate-housing', 'Corporate housing', 'Furnished corporate housing for assignments and projects — flexible terms, one invoice.', 'housing.corporate', 'Request proposal', 'building', 4],
            ['employee-mobility', 'fleet', 'Fleet', 'Chauffeured fleet for guests, assignees and leadership — trained drivers, tracked rides.', 'fleet', 'Book / request fleet', 'globe', 5],
            ['business-mobility', 'travel', 'Travel', 'Corporate travel desk for flights, hotels and ground transport with policy-aware booking.', 'travel', 'Talk to travel desk', 'plane', 0],
            ['business-mobility', 'business-space', 'Business space', 'Office search, fit-out coordination and workspace solutions for growing teams.', 'space', 'Enquire', 'building', 1],
            ['business-mobility', 'recruitment', 'Recruitment', 'Recruitment support for mobility-heavy roles — screened shortlists, honest timelines.', 'recruitment', 'Send requirement', 'user-check', 2],
            ['business-mobility', 'interior-design', 'Interior design', 'Interior design and fit-out for offices and residences — planned, priced and delivered.', 'interiors', 'Book walkthrough', 'building', 3],
            ['business-mobility', 'sanitization', 'Sanitization', 'Facilities care and sanitization for homes, offices and move-outs.', 'facilities', 'Schedule', 'shield-check', 4],
        ];

        foreach ($leaves as [$parent, $slug, $name, $desc, $leadTag, $cta, $icon, $sort]) {
            $service = Service::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'family' => $parent === 'business-mobility' ? ServiceFamily::BusinessMobility->value : ($parent === 'employee-mobility' ? ServiceFamily::EmployeeMobility->value : ServiceFamily::Standalone->value),
                    'parent_id' => $ids[$parent],
                    'name' => $name,
                    'short_desc' => $desc,
                    'intro' => $desc.' Delivered by our own field teams with a named, accountable owner from enquiry to completion.',
                    'icon_svg_key' => $icon,
                    'status' => ServiceStatus::Published->value,
                    'sort' => $sort,
                    'lead_tag' => $leadTag,
                    'meta_title' => $name.' Services in India',
                    'meta_description' => $desc.' Part of the Sewa Hospitality employee and business mobility catalog.',
                    'cta_label_override' => $cta,
                    'content_blocks' => [
                        ['type' => 'feature_grid', 'data' => [
                            'columns' => '3',
                            'style' => 'border',
                            'items' => [
                                ['title' => 'One accountable owner', 'text' => 'A named consultant owns your file end to end.', 'icon' => 'user-check', 'url' => ''],
                                ['title' => 'Written scopes', 'text' => 'What we promise in writing is what we deliver on site.', 'icon' => 'file-text', 'url' => ''],
                                ['title' => 'Care, delivered.', 'text' => 'Details handled so you can focus on work and family.', 'icon' => 'shield-check', 'url' => ''],
                            ],
                        ]],
                        ['type' => 'cta_band', 'data' => [
                            'headline' => 'Plan it with a consultant.',
                            'copy' => 'Tell us the brief — we reply with a scoped plan.',
                            'theme' => 'deep',
                            'layout' => 'split',
                            'ctas' => [['label' => $cta, 'url' => '/contact', 'variant' => 'primary']],
                        ]],
                    ],
                ],
            );

            if ($slug === 'immigration') {
                $ids['immigration-hub'] = $service->getKey();
            }
        }

        // The three immigration children live at /services/immigration/*
        // (catalog rows 3–5) — parented to the immigration hub.
        $immigrationChildren = [
            ['inbound-immigration', 'Inbound immigration', 'FRRO registration, employment visas and entry compliance for assignees arriving in India.', 'immigration.inbound', 'Start registration', 0],
            ['outbound-immigration', 'Outbound immigration', 'Visas, attestations and compliance for India-based employees moving abroad.', 'immigration.outbound', 'Consult', 1],
            ['ancillary-services', 'Ancillary services', 'PAN cards, driving licences and the paperwork around a move — handled alongside the main file.', 'immigration.ancillary', 'Request service', 2],
        ];

        foreach ($immigrationChildren as $i => [$slug, $name, $desc, $leadTag, $cta, $sort]) {
            Service::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'family' => ServiceFamily::Standalone->value,
                    'parent_id' => $ids['immigration-hub'],
                    'name' => $name,
                    'short_desc' => $desc,
                    'intro' => $desc.' Every case gets a compliance timeline with dated checkpoints — you always know what is next.',
                    'icon_svg_key' => 'file-text',
                    'status' => ServiceStatus::Published->value,
                    'sort' => $sort,
                    'lead_tag' => $leadTag,
                    'meta_title' => $name.' — India',
                    'meta_description' => $desc.' Handled by the Sewa Hospitality immigration desk.',
                    'cta_label_override' => $cta,
                    'content_blocks' => [
                        ['type' => 'accordion', 'data' => [
                            'first_open' => true,
                            'items' => [
                                ['title' => 'Your compliance timeline', 'body_html' => '<p>A dated checklist from document intake to completion — visible progress, no surprises.</p>'],
                                ['title' => 'Human review on every filing', 'body_html' => '<p>AI may draft summaries; a person checks every submission before it is filed.</p>'],
                            ],
                        ]],
                        ['type' => 'cta_band', 'data' => [
                            'headline' => 'Start with a consultation.',
                            'copy' => 'Bring the case details — we map the timeline on the call.',
                            'theme' => 'brand',
                            'layout' => 'centered',
                            'ctas' => [['label' => $cta, 'url' => '/contact', 'variant' => 'primary']],
                        ]],
                    ],
                ],
            );
        }

        cache()->forget('services.tree');
        cache()->forget('services.hub');
    }
}
