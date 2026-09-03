{{-- <x-alert> — persistent (never self-destructs), dismissible on demand,
     ARIA-announced (ui-components doc). Roles: info | success | warning | danger. --}}
@props([
    'role' => 'info',
    'dismissible' => false,
])

@php
    $tones = [
        'info' => 'border-line bg-paper-2 text-ink',
        'success' => 'border-success-500/40 bg-success-500/10 text-ink',
        'warning' => 'border-warning-500/40 bg-warning-500/10 text-ink',
        'danger' => 'border-danger-500/40 bg-danger-500/10 text-ink',
    ];
@endphp

<div x-data="{ shown: true }"
     x-show="shown"
     role="{{ $role === 'danger' ? 'alert' : 'status' }}"
     aria-live="polite"
     {{ $attributes->class(['flex items-start gap-3 rounded-xl border p-4 text-sm', $tones[$role] ?? $tones['info']]) }}>
    <div class="min-w-0 flex-1">{{ $slot }}</div>
    @if ($dismissible)
        <button type="button" @click="shown = false"
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-ink-muted hover:bg-paper-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                aria-label="Dismiss">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.72 4.72a.75.75 0 0 1 1.06 0L10 8.94l4.22-4.22a.75.75 0 1 1 1.06 1.06L11.06 10l4.22 4.22a.75.75 0 1 1-1.06 1.06L10 11.06l-4.22 4.22a.75.75 0 0 1-1.06-1.06L8.94 10 4.72 5.78a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
        </button>
    @endif
</div>
