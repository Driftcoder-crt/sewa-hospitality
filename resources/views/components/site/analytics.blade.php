{{-- <x-site-analytics> — consent-gated analytics loader (02-analytics-
     plan §1.1: NO tag fires before consent). Renders NOTHING unless an
     explicit `analytics|all` cookie exists AND a measurement id is
     configured. The Consent Mode default call runs before the loader
     so GA4 never collects ads signals (no-ads until consent, §4). --}}
@props([])
@php($granted = \App\Support\Analytics\Consent::analyticsGranted() && \App\Support\Analytics\Consent::configured())
@if ($granted)
    @php($ga4 = \App\Support\Analytics\Consent::ga4Id())
    @php($gtm = \App\Support\Analytics\Consent::gtmId())
    <script nonce="{{ $cspNonce ?? '' }}">
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        // Consent Mode v2 defaults (02-analytics-plan §4): measurement
        // granted by the banner choice; ads signals stay denied at launch.
        gtag('consent', 'update', {
            'analytics_storage': 'granted',
            'ad_storage': 'denied',
            'ad_user_data': 'denied',
            'ad_personalization': 'denied',
        });
    </script>
    @if ($ga4)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4 }}"></script>
        <script nonce="{{ $cspNonce ?? '' }}">
            gtag('js', new Date());
            gtag('config', '{{ $ga4 }}', { 'anonymize_ip': true });
        </script>
    @endif
    @if ($gtm)
        <script nonce="{{ $cspNonce ?? '' }}">
            (function (w, d, s, l, i) {
                w[l] = w[l] || []; w[l].push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
                var f = d.getElementsByTagName(s)[0], j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
                j.async = true; j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
                f.parentNode.insertBefore(j, f);
            })(window, document, 'script', 'dataLayer', '{{ $gtm }}');
        </script>
    @endif
@endif
