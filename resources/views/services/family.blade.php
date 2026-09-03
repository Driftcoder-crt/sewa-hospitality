{{-- /services/{family} — family page (services doc §3): hero + intro +
    child cards + coverage strip + CTA. Auto-composes from published
    children; blocks come from the service's content_blocks. --}}
@extends('layouts.app')

@section('title', $service->displayTitle().' — Sewa Hospitality')
@section('meta_description', $service->meta_description ?? $service->short_desc)

@push('head')
    <link rel="canonical" href="{{ rtrim(config('app.url', 'https://sewahospitality.com'), '/') }}{{ $service->publicPath() }}">
    <x-site.hreflang :alternates="\App\Modules\I18n\Services\ContentVariants::alternatesFor($service)" />
    <meta name="robots" content="{{ $service->noindex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large' }}">
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
        $blocks,
    ), 'leadIndex' => 0])

    @if ($children->isNotEmpty())
        <section data-theme="light" class="px-4 py-12 md:px-6 md:py-16">
            <div class="container mx-auto">
                <h2 class="font-display text-2xl md:text-3xl">Under this family</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($children as $child)
                        <x-service-card :service="$child" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('services.partials.coverage-strip', ['coverage' => $coverage])
@endsection
