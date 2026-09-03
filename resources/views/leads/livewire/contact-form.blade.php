{{-- Contact form island (03-leads-crm §3). Draft-persistent, inline
     validation, honest success path → /thank-you. --}}
<div class="rounded-2xl border border-line bg-paper-2 p-5 md:p-6">
    @if ($status === 'success')
        <div class="flex flex-col items-start gap-3" role="status">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand text-brand-ink" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
            </span>
            <h3 class="font-display text-xl">Message received</h3>
            <p class="text-sm text-ink-soft">Redirecting you to your confirmation…</p>
        </div>
    @else
        <form wire:submit="submitLead" class="flex flex-col gap-4" novalidate>
            <x-leads.guards />

            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.field name="form.name" label="Full name" :error="$errors->first('form.name')" required>
                    <input id="form.name" type="text" wire:model.live.debounce.300ms="form.name" required autocomplete="name"
                           class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30 @error('form.name') border-danger-500 @enderror">
                </x-form.field>

                <x-form.field name="form.email" label="Email" :error="$errors->first('form.email')" required>
                    <input id="form.email" type="email" wire:model.live.debounce.300ms="form.email" required autocomplete="email"
                           class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30 @error('form.email') border-danger-500 @enderror">
                </x-form.field>

                <x-form.field name="form.phone" label="Phone" :error="$errors->first('form.phone')" required hint="We call, we don't spam.">
                    <input id="form.phone" type="tel" wire:model.live.debounce.300ms="form.phone" required autocomplete="tel"
                           class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30 @error('form.phone') border-danger-500 @enderror">
                </x-form.field>

                <x-form.field name="form.company" label="Company" :error="$errors->first('form.company')" hint="Optional — helps us prepare.">
                    <input id="form.company" type="text" wire:model.live.debounce.300ms="form.company" autocomplete="organization"
                           class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30">
                </x-form.field>
            </div>

            <x-form.field name="form.service_id" label="What do you need?" :error="$errors->first('form.service_id')">
                <select id="form.service_id" wire:model.live="form.service_id"
                        class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30">
                    <option value="">General enquiry</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                    @endforeach
                </select>
            </x-form.field>

            <x-form.field name="form.message" label="Message" :error="$errors->first('form.message')" required>
                <textarea id="form.message" wire:model.live.debounce.300ms="form.message" rows="4" required
                          class="w-full rounded-lg border border-line bg-paper px-3 py-2.5 text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30 @error('form.message') border-danger-500 @enderror"
                          placeholder="Tell us about your move — who, where, when."></textarea>
            </x-form.field>

            <label class="flex items-start gap-3 text-sm text-ink-soft">
                <input type="checkbox" wire:model.live="form.consent"
                       class="mt-1 h-5 w-5 shrink-0 rounded border-line text-brand focus:ring-brand/30">
                <span>I agree that Sewa Hospitality may contact me about this enquiry. See our <a href="/legal/privacy-policy" class="font-medium text-brand underline underline-offset-2">privacy policy</a>.</span>
            </label>
            @error('form.consent') <p class="text-xs font-medium text-danger-500" role="alert">{{ $message }}</p> @enderror

            @error('form') <p class="text-sm font-medium text-danger-500" role="alert">{{ $message }}</p> @enderror

            <div class="flex items-center gap-3">
                <button type="submit"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink transition-opacity hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:opacity-50"
                        wire:loading.attr="disabled" wire:target="submitLead">
                    <span wire:loading.remove wire:target="submitLead">Send message</span>
                    <span wire:loading wire:target="submitLead" class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" class="opacity-75"/></svg>
                        Sending…
                    </span>
                </button>
                <p class="text-xs text-ink-muted">Replies within 2 business hours.</p>
            </div>
        </form>
    @endif
</div>
