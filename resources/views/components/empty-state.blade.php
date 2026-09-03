{{-- <x-empty-state> — guidance + CTA; lists, search and filters never
     dead-end (ui-components doc). --}}
@props([
    'title',
    'description' => null,
])

<div class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-line bg-paper-2 p-8 text-center">
    <p class="font-display text-lg">{{ $title }}</p>
    @if ($description)
        <p class="max-w-md text-sm text-ink-soft">{{ $description }}</p>
    @endif
    @if (trim($slot))
        <div class="mt-2">{{ $slot }}</div>
    @endif
</div>
