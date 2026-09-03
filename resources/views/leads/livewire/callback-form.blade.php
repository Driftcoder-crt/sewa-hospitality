{{-- Callback island — phone-first (2-hour SLA promise, 03-leads-crm §3). --}}
<div class="rounded-2xl border border-line bg-paper-2 p-5 md:p-6">
    @if ($status === 'success')
        <div class="flex flex-col items-start gap-3" role="status">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand text-brand-ink" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2 3a1 1 0 0 1 1-1h2.153a1 1 0 0 1 .986.836l.74 4.435a1 1 0 0 1-.54 1.06l-1.548.773a11.037 11.037 0 0 0 6.105 6.105l.774-1.548a1 1 0 0 1 1.059-.54l4.435.74a1 1 0 0 1 .836.986V17a1 1 0 0 1-1 1h-2C7.82 18 2 12.18 2 5V3Z" clip-rule="evenodd"/></svg>
            </span>
            <h3 class="font-display text-xl">We'll call you</h3>
            <p class="text-sm text-ink-soft">Redirecting you to your confirmation…</p>
        </div>
    @else
        <form wire:submit="submitLead" class="flex flex-col gap-4" novalidate>
            <x-leads.guards />

            <x-form.field name="form.phone" label="Your phone number" :error="$errors->first('form.phone')" required>
                <input id="form.phone" type="tel" wire:model.live.debounce.300ms="form.phone" required autocomplete="tel" inputmode="tel"
                       class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30 @error('form.phone') border-danger-500 @enderror">
            </x-form.field>

            <x-form.field name="form.name" label="Your name" :error="$errors->first('form.name')" hint="Optional — it makes the call friendlier.">
                <input id="form.name" type="text" wire:model.live.debounce.300ms="form.name" autocomplete="name"
                       class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30">
            </x-form.field>

            <x-form.field name="form.window" label="Best time to call" :error="$errors->first('form.window')">
                <select id="form.window" wire:model.live="form.window"
                        class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30">
                    <option value="asap">As soon as possible</option>
                    <option value="morning">Morning (09:00–12:00 IST)</option>
                    <option value="afternoon">Afternoon (12:00–17:00 IST)</option>
                    <option value="evening">Evening (17:00–19:00 IST)</option>
                </select>
            </x-form.field>

            <label class="flex items-start gap-3 text-sm text-ink-soft">
                <input type="checkbox" wire:model.live="form.consent"
                       class="mt-1 h-5 w-5 shrink-0 rounded border-line text-brand focus:ring-brand/30">
                <span>I agree to receive a call from Sewa Hospitality about my request. <a href="/legal/privacy-policy" class="font-medium text-brand underline underline-offset-2">Privacy policy</a>.</span>
            </label>
            @error('form.consent') <p class="text-xs font-medium text-danger-500" role="alert">{{ $message }}</p> @enderror

            @error('form') <p class="text-sm font-medium text-danger-500" role="alert">{{ $message }}</p> @enderror

            <button type="submit"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink transition-opacity hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:opacity-50"
                    wire:loading.attr="disabled" wire:target="submitLead">
                <span wire:loading.remove wire:target="submitLead">Request callback</span>
                <span wire:loading wire:target="submitLead" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" class="opacity-75"/></svg>
                    Sending…
                </span>
            </button>
            <p class="text-xs text-ink-muted">We call back within 2 business hours.</p>
        </form>
    @endif
</div>
