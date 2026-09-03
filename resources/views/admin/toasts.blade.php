{{-- Admin toast island — persistent until dismissed (never self-
     destructs), aria-live announced, token-driven. --}}
<div class="pointer-events-none fixed bottom-4 end-4 z-50 flex w-full max-w-sm flex-col gap-2">
    @foreach ($toasts as $toast)
        @php
            $tones = [
                'success' => 'border-success-500/40 bg-paper',
                'danger' => 'border-danger-500/40 bg-paper',
                'info' => 'border-line bg-paper',
            ];
        @endphp
        <div class="pointer-events-auto flex items-start gap-3 rounded-xl border p-4 shadow-lg {{ $tones[$toast['tone']] ?? $tones['info'] }}"
             role="{{ $toast['tone'] === 'danger' ? 'alert' : 'status' }}"
             aria-live="polite">
            <p class="min-w-0 flex-1 text-sm text-ink">{{ $toast['message'] }}</p>
            <button wire:click="dismiss('{{ $toast['id'] }}')" type="button"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-ink-muted hover:bg-paper-3"
                    aria-label="Dismiss">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.72 4.72a.75.75 0 0 1 1.06 0L10 8.94l4.22-4.22a.75.75 0 1 1 1.06 1.06L11.06 10l4.22 4.22a.75.75 0 1 1-1.06 1.06L10 11.06l-4.22 4.22a.75.75 0 0 1-1.06-1.06L8.94 10 4.72 5.78a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
            </button>
        </div>
    @endforeach
</div>
