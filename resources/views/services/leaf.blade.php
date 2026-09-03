{{-- /services/{parent}/{leaf} — leaf page (services doc §3): hero,
    intro, scope blocks, coverage strip, related services, FAQ schema
    comes from the faq block. lead_tag rides on M3 forms. --}}
@extends('layouts.app')

@section('title', $service->displayTitle().' — Sewa Hospitality')
@section('meta_description', $service->meta_description ?? $service->short_desc)

@push('head')
    <link rel="canonical" href="{{ rtrim(config('app.url', 'https://sewahospitality.com'), '/') }}{{ $service->publicPath() }}">
    <x-site.hreflang :alternates="\App\Modules\I18n\Services\ContentVariants::alternatesFor($service)" />
    <meta name="robots" content="{{ $service->noindex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large' }}">
    @if ($service->faq)
        {{-- FAQPage schema ONLY because answers are visibly rendered by the faq block (schema-matches-visible rule).
             JSON-LD precomputed; the @json directive with a multi-line array literal breaks the compilers. --}}
        @php
            $faqSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => collect($service->faq)->map(fn (array $item): array => [
                    '@type' => 'Question',
                    'name' => $item['q'] ?? '',
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a'] ?? ''],
                ])->values()->all(),
            ];
        @endphp
        <script type="application/ld+json">
            {!! json_encode($faqSchema, 15) !!}
        </script>
    @endif
@endpush

@section('content')
    @include('cms.partials.blocks', ['blocks' => array_merge(
        [['type' => 'hero', 'data' => [
            'eyebrow' => mb_strtoupper($service->family->label()),
            'headline' => $service->name,
            'sub' => $service->short_desc,
            'height' => 'compact',
            'overlay' => 'none',
            'align' => 'start',
            'ctas' => [['label' => $service->cta_label_override ?? 'Talk to a consultant', 'url' => '/contact', 'variant' => 'primary']],
        ]]],
        $service->intro ? [['type' => 'rich_text', 'data' => ['html' => '<h2>Overview</h2><p>'.e($service->intro).'</p>']]] : [],
        $blocks,
    ), 'leadIndex' => 0])

    @include('services.partials.coverage-strip', ['coverage' => $coverage])

    @if ($related->isNotEmpty())
        <section data-theme="light" class="px-4 py-12 md:px-6">
            <div class="container mx-auto">
                <h2 class="font-display text-2xl md:text-3xl">You may also need</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $sibling)
                        <x-service-card :service="$sibling" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Per-service quote form (M3): the pre-set lead_tag context rides
         via the service id — every submission is tagged in the CRM. --}}
    @php($contextService = $service)
    <x-dynamic-component :component="\App\Modules\Cms\Services\BlockRegistry::component('lead_form')"
        :data="[
            'form_type' => 'quote',
            'heading' => 'Get a quote for '.($contextService->name),
            'intro' => 'Share the essentials — a consultant comes back with a concrete, honest proposal.',
            'service_id' => $contextService->getKey(),
            'benefits' => [['text' => 'Proposal-ready reply within 4 business hours'], ['text' => 'No obligation, no recycled pricing'], ['text' => 'Your request lands with the right specialist directly']],
            'privacy_note' => 'We use your details only to answer this enquiry — never for marketing lists without consent.',
        ]"
    />
@endsection
