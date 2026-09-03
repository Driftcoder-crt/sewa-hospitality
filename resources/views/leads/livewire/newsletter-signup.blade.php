{{-- Newsletter capture island (E4) — inline confirmation, double opt-in
     on the way (the confirm email does the actual subscribing). --}}
<div @class(['w-full', 'max-w-md' => ! $compact])>
    @if ($subscribed)
        <div class="flex items-start gap-3 rounded-xl border border-line bg-paper-3 p-4" role="status">
            <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand text-brand-ink" aria-hidden="true">
                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
            </span>
            <div>
                <p class="text-sm font-semibold text-ink">Almost there — check your inbox</p>
                <p class="mt-1 text-sm text-ink-soft">We sent a confirmation link. One click and you're on the list.</p>
            </div>
        </div>
    @else
        <form wire:submit="submitNewsletter" @class(['flex w-full items-start gap-2', 'flex-col sm:flex-row' => $compact]) novalidate>
            <x-leads.guards />

            <label class="sr-only">
                <span>Email address</span>
                <input type="email"
                   wire:model.live.debounce.300ms="email"
                   required
                   autocomplete="email"
                   placeholder="you@company.com"
                   class="min-h-[44px] w-full rounded-full border border-line bg-paper px-4 text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30 @error('email') border-danger-500 @enderror">
            </label>

            <button type="submit"
                    class="inline-flex min-h-[44px] shrink-0 items-center justify-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink transition-opacity hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:opacity-50"
                    wire:loading.attr="disabled" wire:target="submitNewsletter">
                <span wire:loading.remove wire:target="submitNewsletter">Subscribe</span>
                <span wire:loading wire:target="submitNewsletter">…</span>
            </button>
        </form>
        @error('email') <p class="mt-2 text-xs font-medium text-danger-500" role="alert">{{ $message }}</p> @enderror
        @error('form') <p class="mt-2 text-xs font-medium text-danger-500" role="alert">{{ $message }}</p> @enderror
        @unless ($compact)
            <p class="mt-2 text-xs text-ink-muted">Relocation guides, city notes and housing updates. No spam — one click to leave.</p>
        @endunless
    @endif
</div>
