{{-- /dev/components — the visual-state gallery (05-design-system/
     02-ui-components.md §3 + section-library §10): every wave block in
     every theme slot, one page, noindex always, local/dev only.
     Theme switcher swaps data-theme on the <html> element (Alpine). --}}
@extends('layouts.app')

@section('title', 'Component gallery (dev) — Sewa Hospitality')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <div x-data="{ theme: 'light' }" x-init="$watch('theme', t => document.documentElement.setAttribute('data-theme', t))">
        <section data-theme="light" class="border-b border-line px-4 py-10 md:px-6">
            <div class="container mx-auto">
                <p class="eyebrow text-ink-muted">COMPONENT GALLERY</p>
                <h1 class="font-display mt-2 text-3xl">Component gallery</h1>
                <p class="mt-2 max-w-2xl text-ink-soft">The complete 49-block enumerated catalog (all four waves — every block listed in section-library §2–§7), rendered in every theme slot. Gallery samples use placeholder media slots — media-specific states render live through the media pipeline.</p>
                <div class="mt-5 flex flex-wrap gap-2" role="group" aria-label="Theme preview">
                    @foreach (['light', 'dark', 'brand', 'deep'] as $t)
                        <button type="button" @click="theme = '{{ $t }}'"
                                :aria-pressed="(theme === '{{ $t }}').toString()"
                                class="inline-flex min-h-[44px] items-center rounded-full border px-5 text-sm font-semibold transition-colors"
                                :class="theme === '{{ $t }}' ? 'border-brand bg-brand text-brand-ink' : 'border-line text-ink-soft hover:bg-paper-3'">
                            {{ ucfirst($t) }}
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        @php
            $gallery = [
                ['A1 · Hero', 'x-blocks.hero', [
                    'eyebrow' => 'SEWA HOSPITALITY',
                    'headline' => 'Care, delivered.',
                    'sub' => 'Corporate relocation, global mobility and hospitality services across India.',
                    'height' => 'compact', 'overlay' => 'none', 'align' => 'start',
                    'ctas' => [
                        ['label' => 'Talk to a consultant', 'url' => '#', 'variant' => 'primary'],
                        ['label' => 'Call us', 'url' => '#', 'variant' => 'secondary'],
                    ],
                ]],
                ['A2 · Split Hero', 'x-blocks.split-hero', [
                    'eyebrow' => 'SPLIT LAYOUT',
                    'headline' => 'Asymmetric, editorial.',
                    'sub' => 'Media side alternates; copy breathes on the opposite column.',
                    'media_side' => 'right',
                    'ctas' => [['label' => 'See how we work', 'url' => '#', 'variant' => 'primary']],
                ]],
                ['A3 · Section Wrapper', 'x-blocks.section-wrapper', [
                    'eyebrow' => 'FRAMING PRIMITIVE',
                    'title' => 'A framed section',
                    'intro' => 'Other blocks sit inside this wrapper — theme slot, anchor id, density.',
                    'density' => 'compact',
                    'slot' => '<p class="rounded-xl border border-line bg-paper-2 p-6 text-sm">Wrapped content slot (any block nests here).</p>',
                ]],
                ['A4 · Feature Grid', 'x-blocks.feature-grid', [
                    'columns' => '3', 'style' => 'border',
                    'items' => [
                        ['title' => 'Global mobility', 'text' => 'Immigration and relocation for international teams.', 'icon' => 'globe', 'url' => ''],
                        ['title' => 'Corporate housing', 'text' => 'Serviced apartments verified by our own standard.', 'icon' => 'building', 'url' => ''],
                        ['title' => 'City coverage', 'text' => 'Honest, dated local information across India.', 'icon' => 'map', 'url' => ''],
                    ],
                ]],
                ['B1 · Rich Text', 'x-blocks.rich-text', [
                    'html' => '<h2>Editorial long-form</h2><p>Sanitized whitelist HTML with <strong>heading-ladder enforcement</strong> — any h1 is demoted to h2, the lead hero owns the single H1.</p><blockquote>Pull-quote style for editorial rhythm.</blockquote><ul><li>Lists, links, tables</li><li>Figures with captions</li></ul>',
                ]],
                ['B2 · Text + Media', 'x-blocks.text-media', [
                    'title' => 'Copy meets imagery',
                    'copy' => '<p>The flagship editorial layout: copy side, media side, caption, flip flag.</p>',
                    'media_side' => 'right',
                ]],
                ['B3 · Chapter Heading', 'x-blocks.chapter-heading', [
                    'number' => '01', 'title' => 'A chapter divider',
                ]],
                ['B4 · Accordion', 'x-blocks.accordion', [
                    'first_open' => true,
                    'items' => [
                        ['title' => 'Single-source DOM', 'body_html' => '<p>Native details/summary + Alpine group — one DOM copy, unlike the reference.</p>'],
                        ['title' => 'One at a time', 'body_html' => '<p>Opening a panel closes the previous one; aria-expanded stays correct.</p>'],
                    ],
                ]],
                ['E1 · CTA Band', 'x-blocks.cta-band', [
                    'headline' => 'Ready to plan your move?',
                    'copy' => 'Tell us who is moving and where — a consultant replies with a scoped plan.',
                    'theme' => 'brand', 'layout' => 'centered',
                    'ctas' => [['label' => 'Talk to a consultant', 'url' => '#', 'variant' => 'primary']],
                ]],
                ['E1 · CTA Band (deep, split)', 'x-blocks.cta-band', [
                    'headline' => 'Prefer we call you?',
                    'copy' => 'Split layout on the deep ink theme.',
                    'theme' => 'deep', 'layout' => 'split',
                    'ctas' => [['label' => 'Contact us', 'url' => '#', 'variant' => 'primary']],
                ]],

                // ── Wave 2 (M2): C1–C5, D1–D3, B5–B9 ──
                ['B5 · Tabs', 'x-blocks.tabs', [
                    'items' => [
                        ['label' => 'Offices', 'content_html' => '<p>Our Gurugram HQ plus client desks across India.</p>'],
                        ['label' => 'Hours', 'content_html' => '<p>Mon–Sat, 9:00–19:00 IST. Urgent move support 24/7.</p>'],
                    ],
                ]],
                ['B6 · Timeline', 'x-blocks.timeline', [
                    'items' => [
                        ['date' => 'Week 0', 'title' => 'Plan & scope', 'text' => 'A named consultant maps the move and the budget.'],
                        ['date' => 'Week 2', 'title' => 'Home secured', 'text' => 'Sewa Verified shortlists, lease signed.'],
                        ['date' => 'Week 4', 'title' => 'Settled in', 'text' => 'FRRO done, school run running, first grocery done.'],
                    ],
                ]],
                ['B7 · FAQ', 'x-blocks.faq', [
                    'heading' => 'Questions people actually ask',
                    'items' => [
                        ['q' => 'How fast can you move a family to Gurugram?', 'a' => 'Most families land in a verified home within two weeks of the brief — immigration timelines are published per case with dated checkpoints.'],
                        ['q' => 'Are the rates on housing pages final?', 'a' => 'No — they are honest from-rates with an as-of date. You receive an exact quote before anything is booked.'],
                    ],
                ]],
                ['B8 · Comparison Table', 'x-blocks.comparison-table', [
                    'heading' => 'Serviced apartment vs hotel',
                    'highlight' => '2',
                    'columns' => [['label' => 'Hotel (30+ nights)'], ['label' => 'Serviced apartment']],
                    'rows' => [
                        ['label' => 'Kitchen', 'values' => 'No, Yes'],
                        ['label' => 'Monthly cost', 'values' => 'Higher, Lower'],
                        ['label' => 'Housekeeping', 'values' => 'Daily, Scheduled'],
                    ],
                ]],
                ['B9 · Story Pillars', 'x-blocks.story-pillars', [
                    'items' => [
                        ['title' => 'Accountable', 'hook' => 'A named owner for every move and every ticket.'],
                        ['title' => 'Transparent', 'hook' => 'Written scopes, dated rates, no surprises.'],
                        ['title' => 'Careful', 'hook' => 'Verified homes, trained field teams.'],
                    ],
                ]],
                ['C1 · Gallery Grid', 'x-blocks.gallery-grid', [
                    'columns' => '3', 'aspect' => 'landscape',
                    'items' => [
                        ['media_id' => null, 'caption' => 'Placeholder slot (media renders via pipeline)'],
                        ['media_id' => null, 'caption' => 'Aspect 3:2'],
                        ['media_id' => null, 'caption' => 'Caption under image'],
                    ],
                ]],
                ['C2 · Carousel', 'x-blocks.carousel', [
                    'items' => [
                        ['media_id' => null, 'caption' => 'Slide 1 — scroll-snap'],
                        ['media_id' => null, 'caption' => 'Slide 2 — arrows + dots'],
                        ['media_id' => null, 'caption' => 'Slide 3 — keyboard operable'],
                    ],
                ]],
                ['C4 · Video Feature (facade)', 'x-blocks.video-feature', [
                    'title' => 'A move, end to end',
                    'youtube_id' => 'dQw4w9WgXcQ',
                    'caption' => 'Iframe loads on intent only — zero third-party JS pre-consent.',
                ]],
                ['C5 · Logo Cloud (honest zero-state)', 'x-blocks.logo-cloud', ['source' => 'memberships']],
                ['D1 · Testimonial Grid (zero-state)', 'x-blocks.testimonial-grid', ['source' => 'home', 'limit' => '4']],
                ['D2 · Review Highlights (zero-state)', 'x-blocks.review-highlights', ['link_reviews' => true]],
                ['D3 · Stats Band', 'x-blocks.stats-band', [
                    'as_of' => 'Sample values — real counters publish only with dated data',
                    'items' => [
                        ['value' => '12000', 'suffix' => '+', 'label' => 'Sample moves supported'],
                        ['value' => '7', 'suffix' => '', 'label' => 'Sample hub cities'],
                        ['value' => '98', 'suffix' => '%', 'label' => 'Sample on-time handovers'],
                        ['value' => '24', 'suffix' => '/7', 'label' => 'Urgent support'],
                    ],
                ]],

                // ── Wave 3 (M3): E2–E8, D4–D6 ──
                ['E2 · Lead Form (contact island)', 'x-blocks.lead-form', [
                    'form_type' => 'contact',
                    'heading' => 'Talk to a consultant',
                    'intro' => 'The real form island — Turnstile, honeypot, idempotency and the SLA pipeline are wired.',
                    'benefits' => [['text' => 'Reply within 2 business hours'], ['text' => 'A named consultant, not a ticket queue']],
                    'privacy_note' => 'We use your details only to answer this enquiry.',
                ]],
                ['E2 · Lead Form (callback island)', 'x-blocks.lead-form', [
                    'form_type' => 'callback',
                    'heading' => 'Prefer a call?',
                    'benefits' => [['text' => 'Call back within 2 business hours']],
                ]],
                ['E3 · Offer Banner (dismissible)', 'x-blocks.offer-banner', [
                    'heading' => 'Fleet month: free airport meet & greet',
                    'code' => 'SEWA-GREET',
                    'cta_label' => 'See the fleet', 'cta_url' => '#',
                    'theme' => 'brand',
                ]],
                ['E4 · Newsletter Capture (inline)', 'x-blocks.newsletter-capture', [
                    'variant' => 'inline', 'theme' => 'light',
                    'heading' => 'Relocation guides, honest city notes',
                    'copy' => 'Double opt-in — the real island, the real pipeline.',
                    'note' => 'A few times a month. One click to leave.',
                ]],
                ['E5 · Promo Card Grid', 'x-blocks.promo-card-grid', [
                    'columns' => '3',
                    'items' => [
                        ['title' => 'Extended stay', 'terms' => '30+ nights on serviced apartments, rates locked for the whole stay.', 'badge' => 'Housing', 'cta_label' => 'Browse homes', 'cta_url' => '#', 'validity' => 'Rolling'],
                        ['title' => 'Family arrival bundle', 'terms' => 'Airport meet, SIM, school run orientation in one package.', 'badge' => 'Settling-in', 'cta_label' => 'Talk to us', 'cta_url' => '#', 'validity' => 'Sample terms'],
                        ['title' => 'Fleet month', 'terms' => 'Complimentary meet & greet on monthly fleet packages.', 'badge' => 'Fleet', 'cta_label' => 'See fleet', 'cta_url' => '#', 'validity' => 'Sample terms'],
                    ],
                ]],
                ['E6 · Countdown Promo (expires gracefully)', 'x-blocks.countdown-promo', [
                    'heading' => 'Immigration clinic — Q3 window',
                    'copy' => 'Free 20-minute document checks for visa applications booked this quarter.',
                    'deadline' => now()->addDays(30)->toIso8601String(),
                    'cta_label' => 'Book a slot', 'cta_url' => '#',
                    'theme' => 'deep',
                ]],
                ['E7 · Exit-Intent Modal (1/7d cap, never first paint)', 'x-blocks.exit-intent-modal', [
                    'trigger' => 'time', 'delay_seconds' => '8',
                    'heading' => 'Before you go — the honest FAQ',
                    'copy' => 'The 12 questions every expat asks, answered with dated facts.',
                    'mode' => 'cta',
                    'ctas' => [['label' => 'Read the FAQ', 'url' => '#', 'variant' => 'primary']],
                ]],
                ['E8 · Sticky CTA Bar (mobile bottom + desktop rail)', 'x-blocks.sticky-cta-bar', [
                    'heading' => 'Need us?',
                    'items' => [
                        ['label' => 'Call now', 'url' => 'tel:+919873255531', 'icon' => 'call'],
                        ['label' => 'WhatsApp', 'url' => '#', 'icon' => 'whatsapp'],
                        ['label' => 'Contact', 'url' => '#', 'icon' => 'chat'],
                    ],
                ]],
                ['D4 · Trust Checklist', 'x-blocks.trust-checklist', [
                    'heading' => 'The Sewa Verified standard',
                    'items' => [
                        ['text' => 'Physical inspection by our own field team'],
                        ['text' => 'Safe wiring, water and backup power verified'],
                        ['text' => 'Honest from-rates with an as-of date'],
                        ['text' => 'Re-verified every six months'],
                    ],
                ]],
                ['D5 · Case Story', 'x-blocks.case-story', [
                    'client_label' => 'A technology company relocating 40 engineers',
                    'challenge' => 'Forty engineers, three cities, one quarter — housing and immigration running in parallel with product deadlines.',
                    'approach' => 'A named lead per city, weekly dated checkpoints, verified shortlists only.',
                    'outcome' => 'Every engineer in a verified home before start dates; zero visa escalations.',
                    'metrics' => [['value' => '40', 'label' => 'Moves'], ['value' => '0', 'label' => 'Escalations'], ['value' => '11 days', 'label' => 'Median home-in date']],
                ]],
                ['D6 · Team Grid (module-fed, honest zero-state)', 'x-blocks.team-grid', [
                    'heading' => 'The people behind the moves',
                    'department' => 'all', 'limit' => '8',
                ]],

                // ── Wave 4 (M4): A5–A8, C6–C8, F1–F10 — completes the 49-block catalog ──
                ['A5 · Bento Grid', 'x-blocks.bento-grid', [
                    'items' => [
                        ['title' => 'One consultant, end to end', 'text' => 'A single accountable owner from landing to lease sign.', 'size' => 'wide'],
                        ['title' => 'Verified homes only', 'text' => 'Every unit physically inspected before you see it.', 'size' => 'tall'],
                        ['title' => 'SLA-backed replies', 'text' => '2 business hours.', 'size' => 'small'],
                    ],
                ]],
                ['A6 · Step Flow', 'x-blocks.step-flow', [
                    'items' => [
                        ['title' => 'Share your brief', 'text' => 'One form, five minutes — we pick up within two business hours.'],
                        ['title' => 'Get a costed plan', 'text' => 'Line-item quote: housing, schooling, visas, fleet.'],
                        ['title' => 'Land and settle', 'text' => 'Airport greet, home handover, city orientation.'],
                    ],
                ]],
                ['A7 · Marquee Strip (CSS-only, motion-reduce static)', 'x-blocks.marquee-strip', [
                    'items' => [['text' => 'Relocation'], ['text' => 'Immigration'], ['text' => 'Housing'], ['text' => 'Fleet']],
                ]],
                ['A8 · Spacer / Divider', 'x-blocks.spacer-divider', [
                    'height' => 'md', 'ornament' => 'rule',
                ]],
                ['C6 · Image Duo', 'x-blocks.image-duo', [
                    'items' => [
                        ['media_id' => null, 'caption' => 'Arrival day — airport greet'],
                        ['media_id' => null, 'caption' => 'Week one — home handover'],
                    ],
                ]],
                ['C7 · Map Block (click-to-load facade — no pre-consent 3rd-party JS)', 'x-blocks.map-block', [
                    'heading' => 'Find us in Gurugram', 'address' => 'DT Mega Mall, DLF Phase 1, Gurugram',
                    'pin_lat' => '28.4670', 'pin_lng' => '77.0940',
                ]],
                ['C8 · Before / After (range-input keyboard a11y)', 'x-blocks.before-after', [
                    'label_before' => 'Before Sewa', 'label_after' => 'Week one', 'caption' => 'Unfurnished to move-in ready.',
                ]],
                ['F1 · Services Grid (module-fed)', 'x-blocks.services-grid', [
                    'family' => 'all', 'limit' => '6',
                ]],
                ['F2 · Service Accordion', 'x-blocks.service-accordion', [
                    'heading' => 'What each service covers',
                ]],
                ['F3 · Housing Grid (module-fed, honest zero-state)', 'x-blocks.housing-grid', [
                    'city_id' => null, 'limit' => '6',
                ]],
                ['F4 · City Strip (module-fed)', 'x-blocks.city-strip', [
                    'heading' => 'Where we operate', 'limit' => '8',
                ]],
                ['F5 · Posts Feed (module-fed)', 'x-blocks.posts-feed', [
                    'type' => 'blog', 'limit' => '4',
                ]],
                ['F6 · Category Cloud', 'x-blocks.category-cloud', [
                    'heading' => 'Browse the notebook',
                ]],
                ['F7 · Job Listings (module-fed)', 'x-blocks.job-listings', [
                    'heading' => 'Open roles', 'department' => 'all',
                ]],
                ['F8 · Leadership Grid (module-fed)', 'x-blocks.leadership-grid', [
                    'heading' => 'Leadership', 'limit' => '4',
                ]],
                ['F9 · Ventures Strip', 'x-blocks.ventures-strip', [
                    'heading' => 'Group ventures',
                ]],
                ['F10 · Search Widget', 'x-blocks.search-widget', [
                    'heading' => 'Search the site',
                ]],
            ];
        @endphp

        @foreach ($gallery as $entry)
            <section data-theme="light" class="border-b border-line">
                <div class="container mx-auto flex items-center gap-3 px-4 pt-6 md:px-6">
                    <h2 class="text-sm font-bold uppercase tracking-wider">{{ $entry[0] }}</h2>
                    <code class="text-xs text-ink-muted">{{ '<'.$entry[1].'>' }}</code>
                </div>
                <div class="pt-2">
                    @if ($entry[1] === 'x-blocks.section-wrapper')
                        <x-blocks.section-wrapper :data="$entry[2]">
                            {!! $entry[2]['slot'] !!}
                        </x-blocks.section-wrapper>
                    @else
                        <x-dynamic-component :component="$entry[1]" :data="$entry[2]" :is-lead="true" />
                    @endif
                </div>
            </section>
        @endforeach

        <section data-theme="light" class="px-4 py-12 md:px-6">
            <div class="container mx-auto">
                <h2 class="text-sm font-bold uppercase tracking-wider">Atom · Empty state</h2>
                <div class="mt-3 max-w-2xl">
                    <x-empty-state title="Nothing here yet" description="Lists, search and filters always offer a next step — never a dead end.">
                        <x-button href="{{ route('home') }}" variant="secondary" size="sm">Back home</x-button>
                    </x-empty-state>
                </div>
            </div>
        </section>
    </div>
@endsection
