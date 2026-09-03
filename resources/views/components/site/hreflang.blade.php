{{-- <x-site.hreflang :alternates="['ja' => '/ja/services/x']" /> --}}
{{-- hreflang alternates for public indexable views (11-multilingual
     §3/§5). When the caller passes an entity-variant map
     (ContentVariants::alternatesFor), those exact paths render; with
     no argument, the fallback path algebra applies (every enabled
     locale serves this same path — EN renders where variants are
     missing, so the set stays truthful). Never used on noindex
     utility surfaces. --}}
@props([
    'alternates' => null, // ?array<string,string> locale-code => root-relative path
])
@php
    $base = rtrim(config('app.url', 'https://sewahospitality.com'), '/');

    if (is_array($alternates) && $alternates !== []) {
        $map = $alternates;
    } else {
        $current = \App\Modules\I18n\Services\LocaleUrls::stripPrefix(request()->path());
        $map = [];
        foreach (\App\Modules\I18n\Models\Locale::enabledCodes() as $code) {
            $map[$code] = \App\Modules\I18n\Services\LocaleUrls::localized($code, $current);
        }
        $map['x-default'] = $map[\App\Modules\I18n\Models\Locale::DEFAULT] ?? '/';
    }
@endphp
@foreach ($map as $lang => $href)
    <link rel="alternate" hreflang="{{ $lang }}" href="{{ $base }}{{ $href }}">
@endforeach
