{{-- <x-service-card> — icon, name, excerpt, arrow link (ui-components
     doc): consistent across hubs/city pages. On-domain links only
     (ADR-010) — Serviced Apartments/Fleet point into /housing, never
     to sister sites. --}}
@props([
    'service', // App\Modules\Services\Models\Service
])

@php($path = $service->publicPath())
<a href="{{ $path }}"
   class="group flex min-h-[44px] flex-col rounded-2xl border border-line bg-paper p-6 transition-colors hover:bg-paper-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
    @if ($service->icon_svg_key)
        <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-brand/10 text-brand">
            <x-icon name="{{ $service->icon_svg_key }}" class="h-5 w-5" />
        </span>
    @endif
    <h3 class="font-display mt-4 text-xl text-ink">{{ $service->name }}</h3>
    @if ($service->short_desc)
        <p class="mt-2 text-sm leading-relaxed text-ink-soft">{{ $service->short_desc }}</p>
    @endif
    <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand">
        {{ $service->cta_label_override ?? 'Learn more' }}
        <span class="transition-transform group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5" aria-hidden="true">→</span>
    </span>
</a>
