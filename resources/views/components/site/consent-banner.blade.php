{{-- Consent banner (02-analytics-plan.md §1.1/§4): rendered on every
     public page until an explicit choice exists. Alpine-only, no
     external scripts, 44px targets. Clicking either button IS the
     explicit consent act — the sewa_consent cookie is written here and
     nowhere else; the analytics partial loads only on the NEXT render
     (server-checked, never script-injected before consent). --}}
@php($consent = \App\Support\Analytics\Consent::state())
@if ($consent === null)
    <div x-data="{ open: true }" x-cloak
         x-show="open"
         @consent-close.window="open = false"
         role="region" aria-label="Cookie consent"
         class="fixed inset-x-3 bottom-3 z-50 mx-auto max-w-2xl rounded-xl border border-line bg-paper-2 p-4 shadow-lg md:inset-x-6 md:bottom-6">
        <p class="text-sm font-semibold text-ink">Cookies &amp; privacy</p>
        <p class="mt-1 text-sm text-ink-soft">
            We use strictly necessary cookies to run this site. With your permission we also
            measure anonymous usage with GA4 to improve our guides — never ads profiling,
            never your personal details. <a href="/legal/privacy-policy" class="text-brand underline">Privacy notice</a>
        </p>
        <div class="mt-3 flex flex-wrap gap-3">
            <button type="button"
                    @click="document.cookie = 'sewa_consent=all; path=/; max-age=31536000; samesite=lax'; $dispatch('consent-close')"
                    class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Accept analytics
            </button>
            <button type="button"
                    @click="document.cookie = 'sewa_consent=essential; path=/; max-age=31536000; samesite=lax'; $dispatch('consent-close')"
                    class="inline-flex min-h-[44px] items-center rounded-full border border-line px-5 text-sm font-semibold text-ink hover:bg-paper-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Essential only
            </button>
        </div>
    </div>
@endif
