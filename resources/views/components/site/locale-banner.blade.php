{{-- One-time locale suggestion banner (11-multilingual §3): detection
     NEVER redirects — it suggests. Rendered above <main> from the
     layout; visibility is decided by the LocaleResolver-shared $i18n
     payload (suggested ≠ null → no cookie choice yet, visitor hints at
     a non-EN locale). The CTA is an EXPLICIT click — the only path
     that ever sets the sewa_locale cookie — and lands on the same page
     under the suggested locale. Dismiss is a session-only flag. --}}
@php
    $i18n = $i18n ?? [];
    $suggested = $i18n['suggested'] ?? null;
    $current = $i18n['current'] ?? 'en';
    $dismissed = $i18n['dismissed'] ?? false;
    $show = $suggested !== null
        && $suggested !== $current
        && ! $dismissed
        && ($i18n['locales'][$suggested] ?? null) !== null;
    // Banner copy per locale rides the translations table (11-multilingual
    // §6.4); EN default until a reviewed string exists.
    $askCopy = \App\Modules\I18n\Services\UiStrings::get('site', 'locale.banner_ask', $current, 'Read this site in :locale?');
    $yesCopy = \App\Modules\I18n\Services\UiStrings::get('site', 'locale.banner_yes', $current, 'Yes, switch');
    $noCopy = \App\Modules\I18n\Services\UiStrings::get('site', 'locale.banner_no', $current, 'No thanks');
@endphp
@if ($show)
    <aside role="region" aria-label="Language suggestion"
           class="border-b border-line bg-brand/5">
        <div class="container mx-auto flex flex-col items-start gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p class="text-sm text-ink-soft">
                {{ str_replace(':locale', $i18n['locales'][$suggested], $askCopy) }}
            </p>
            <div class="flex items-center gap-3">
                <a href="{{ route('locale.choose', ['code' => $suggested, 'to' => request()->path()]) }}"
                   hreflang="{{ $suggested }}"
                   class="inline-flex min-h-[44px] items-center rounded-lg bg-brand px-4 text-sm font-semibold text-brand-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    {{ $yesCopy }}
                </a>
                <a href="{{ route('locale.dismiss') }}"
                   class="inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm text-ink-soft hover:bg-paper-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    {{ $noCopy }}
                </a>
            </div>
        </div>
    </aside>
@endif
