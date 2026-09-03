{{-- D1 · Testimonial Grid (section-library §5) — module-fed; the
    Testimonials module lands in M4, so until then the block renders
    its graceful zero-state (module-fed blocks never break — §11).
    Source badges + /reviews link arrive with real data. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    // Testimonials module (M4) will feed this via its read interface:
    // \App\Modules\Testimonials\Services\TestimonialService::forSource(...)
    $testimonials = class_exists(\App\Modules\Testimonials\Models\Testimonial::class)
        ? collect()
        : collect();
@endphp

<div {{ $attributes }}>
    <div data-theme="light" class="px-4 py-10 md:px-6 md:py-12">
        <div class="container mx-auto">
            @if ($testimonials->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($testimonials as $testimonial)
                        <blockquote class="rounded-2xl border border-line bg-paper p-6">
                            <p class="text-sm leading-relaxed text-ink-soft">{{ $testimonial->quote }}</p>
                            <footer class="mt-3 text-xs text-ink-muted">{{ $testimonial->attribution }}</footer>
                        </blockquote>
                    @endforeach
                </div>
            @else
                <x-empty-state title="Client stories are being curated"
                    description="We publish real, attributed client feedback only — with source badges. Ask us and we will share references today.">
                    <x-button href="/contact" variant="secondary" size="sm">Request references</x-button>
                </x-empty-state>
            @endif
        </div>
    </div>
</div>
