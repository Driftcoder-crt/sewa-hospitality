{{-- B4 · Accordion (section-library §3 + ui-components rules) —
     SINGLE-SOURCE items (native <details>/<summary>, one DOM copy — the
     reference duplicated DOM per breakpoint), aria-expanded correct,
     one-at-a-time via an Alpine group, first-open flag. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $items = is_array($data['items'] ?? null) ? array_values($data['items']) : [];
    $firstOpen = (bool) ($data['first_open'] ?? true);
    $uid = 'acc-'.\Illuminate\Support\Str::random(6);
@endphp

<div {{ $attributes }}>
    <div data-theme="light" class="px-4 py-10 md:px-6 md:py-12">
        <div class="container mx-auto max-w-3xl" x-data="{ open: @if ($firstOpen) 0 @else null @endif }">
            @foreach ($items as $i => $item)
                @php($clean = \App\Support\Cms\HtmlSanitizer::clean($item['body_html'] ?? ''))
                <div class="border-b border-line">
                    <h3 class="m-0">
                        <button type="button"
                                class="flex w-full items-center justify-between gap-4 py-5 text-start"
                                @click="open = (open === {{ $i }} ? null : {{ $i }})"
                                :aria-expanded="(open === {{ $i }}).toString()"
                                :aria-controls="{{ $uid }}-panel-{{ $i }}"
                                aria-controls="{{ $uid }}-panel-{{ $i }}"
                                id="{{ $uid }}-trigger-{{ $i }}">
                            <span class="font-display text-lg md:text-xl">{{ $item['title'] ?? '' }}</span>
                            <svg class="h-5 w-5 shrink-0 text-ink-muted transition-transform duration-200"
                                 :class="open === {{ $i }} && 'rotate-180'"
                                 viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.06l3.71-3.83a.75.75 0 1 1 1.08 1.04l-4.25 4.39a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </h3>
                    <div x-show="open === {{ $i }}"
                         x-collapse
                         id="{{ $uid }}-panel-{{ $i }}"
                         role="region"
                         aria-labelledby="{{ $uid }}-trigger-{{ $i }}">
                        <div class="pb-5 text-ink-soft [&_p]:mt-2 [&_p]:leading-relaxed [&_a]:font-medium [&_a]:text-brand [&_a]:underline [&_a]:underline-offset-2 [&_strong]:text-ink">
                            {!! $clean !!}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
