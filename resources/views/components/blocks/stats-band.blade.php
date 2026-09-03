{{-- D3 · Stats Band (section-library §5) — 3–5 honest counters with a
    mandatory "as of" line, serif numerals, count-up on intersect
    (Alpine, reduced-motion safe). NO invented values: the seed leaves
    this block unused until real, dated numbers exist. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $items = is_array($data['items'] ?? null) ? array_values($data['items']) : [];
@endphp

<div {{ $attributes }}>
    <div data-theme="brand" class="px-4 py-10 md:px-6 md:py-14"
         x-data="{
            shown: false,
            animate(el) {
                const target = el.dataset.target;
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || isNaN(target)) { el.textContent = el.dataset.label; return; }
                const start = performance.now(); const dur = 900;
                const step = (t) => { const p = Math.min(1, (t - start) / dur); el.textContent = Math.round(target * p).toLocaleString('en-IN'); if (p < 1) requestAnimationFrame(step); };
                requestAnimationFrame(step);
            }
         }"
         x-intersect.once="shown = true; $refs.numbers.querySelectorAll('[data-target]').forEach(animate)">
        <div class="container mx-auto">
            <dl class="grid grid-cols-2 gap-6 text-center md:grid-cols-4" x-ref="numbers">
                @foreach ($items as $item)
                    <div>
                        <dd class="font-display text-3xl text-paper md:text-5xl">
                            <span data-target="{{ preg_replace('/[^0-9.]/', '', (string) ($item['value'] ?? '')) }}"
                                  data-label="{{ $item['value'] ?? '' }}">{{ $item['value'] ?? '' }}</span>{{ $item['suffix'] ?? '' }}
                        </dd>
                        <dt class="mt-1 text-sm text-paper/85">{{ $item['label'] ?? '' }}</dt>
                    </div>
                @endforeach
            </dl>
            <p class="mt-5 text-center text-xs text-paper/70">{{ $data['as_of'] ?? '' }}</p>
        </div>
    </div>
</div>
