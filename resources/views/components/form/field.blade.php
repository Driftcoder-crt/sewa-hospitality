{{-- <x-form.field> — label + control + hint + error slot with inline
     validation state; drafts persist via Livewire bindings (ui-components). --}}
@props([
    'name',
    'label',
    'hint' => null,
    'error' => null,
    'required' => false,
])

<div class="flex flex-col gap-1.5" x-data>
    <label for="{{ $name }}" class="text-sm font-medium text-ink">
        {{ $label }}
        @if ($required) <span class="text-danger-500" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif
    </label>

    {{ $slot }}

    @if ($hint && ! $error)
        <p class="text-xs text-ink-muted">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="text-xs font-medium text-danger-500" role="alert">{{ $error }}</p>
    @endif
</div>
