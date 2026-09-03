{{-- C5 · Logo Cloud (section-library §4) — grouped logos, grayscale→
    color hover, proof links. TRUST RULE: only badges actually held —
    memberships start EMPTY (audit decision), so the block renders its
    honest zero-state until ops adds real entries in settings. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $source = $data['source'] ?? 'memberships';
    $group = app(\App\Modules\Cms\Services\SettingsRepository::class)->get('trust.'.$source, []);
    $items = is_array($group) && $group !== []
        ? collect($group)
        : collect(is_array($data['manual_items'] ?? null) ? $data['manual_items'] : []);
@endphp

<div {{ $attributes }}>
    <div data-theme="light" class="px-4 py-10 md:px-6 md:py-12">
        <div class="container mx-auto">
            @if ($items->isNotEmpty())
                <ul class="flex flex-wrap items-center justify-center gap-x-8 gap-y-4">
                    @foreach ($items as $item)
                        <li class="flex min-h-[44px] items-center">
                            @if (($item['url'] ?? null))
                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener"
                                   class="font-display text-sm text-ink-muted grayscale transition hover:text-ink hover:grayscale-0">
                                    {{ $item['name'] ?? '' }}
                                </a>
                            @else
                                <span class="font-display text-sm text-ink-muted">{{ $item['name'] ?? '' }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                {{-- Honest zero state (trust-authority doc: memberships start empty). --}}
                <p class="text-center text-sm text-ink-muted">
                    Accreditation badges are listed here only once formally held — with a link to the proof.
                </p>
            @endif
        </div>
    </div>
</div>
