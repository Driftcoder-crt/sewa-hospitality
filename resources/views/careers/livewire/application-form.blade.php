{{-- Application form island (06-hr §3): resume ≤5MB, cover message,
     consent, Turnstile + honeypot + idempotency. Typed data is never
     lost on a failed upload — fields stay filled, only the file is
     re-attached (resumable-retry UX rule). --}}
<div class="rounded-2xl border border-line bg-paper-2 p-5">
    @if ($status === 'success')
        <div class="flex flex-col items-start gap-3" role="status">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand text-brand-ink" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
            </span>
            <h3 class="font-display text-xl">Application received</h3>
            <p class="text-sm text-ink-soft">Redirecting you to your confirmation…</p>
        </div>
    @else
        <form wire:submit="submitApplication" class="flex flex-col gap-4" novalidate>
            <x-leads.guards />

            <x-form.field name="applicantName" label="Full name" :error="$errors->first('applicantName')" required>
                <input id="applicantName" type="text" wire:model="applicantName" required autocomplete="name"
                       class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30 @error('applicantName') border-danger-500 @enderror">
            </x-form.field>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.field name="applicantEmail" label="Email" :error="$errors->first('applicantEmail')" required>
                    <input id="applicantEmail" type="email" wire:model="applicantEmail" required autocomplete="email"
                           class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30 @error('applicantEmail') border-danger-500 @enderror">
                </x-form.field>

                <x-form.field name="applicantPhone" label="Phone" :error="$errors->first('applicantPhone')" required>
                    <input id="applicantPhone" type="tel" wire:model="applicantPhone" required autocomplete="tel"
                           class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30 @error('applicantPhone') border-danger-500 @enderror">
                </x-form.field>
            </div>

            <x-form.field name="resume" label="Resume (PDF/DOC/DOCX, ≤ 5 MB)" :error="$errors->first('resume')" required>
                <input id="resume" type="file" wire:model="resume" accept=".pdf,.doc,.docx"
                       class="w-full rounded-lg border border-line bg-paper px-3 py-2.5 text-sm file:me-3 file:rounded-full file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-ink @error('resume') border-danger-500 @enderror">
                <div wire:loading wire:target="resume" class="mt-1 text-xs text-ink-muted">Uploading resume…</div>
            </x-form.field>

            <x-form.field name="coverMessage" label="Why this role?" :error="$errors->first('coverMessage')" required hint="A few honest lines beat a perfect essay.">
                <textarea id="coverMessage" wire:model="coverMessage" rows="4" required
                          class="w-full rounded-lg border border-line bg-paper px-3 py-2.5 text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30 @error('coverMessage') border-danger-500 @enderror"></textarea>
            </x-form.field>

            <label class="flex items-start gap-3 text-sm text-ink-soft">
                <input type="checkbox" wire:model="consent"
                       class="mt-1 h-5 w-5 shrink-0 rounded border-line text-brand focus:ring-brand/30">
                <span>I consent to Sewa Hospitality processing my application data for this and future suitable roles, per the <a href="/legal/privacy-policy" class="font-medium text-brand underline underline-offset-2">privacy policy</a>.</span>
            </label>
            @error('consent') <p class="text-xs font-medium text-danger-500" role="alert">{{ $message }}</p> @enderror

            @error('form') <p class="text-sm font-medium text-danger-500" role="alert">{{ $message }}</p> @enderror

            <button type="submit"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink transition-opacity hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:opacity-50"
                    wire:loading.attr="disabled" wire:target="submitApplication">
                <span wire:loading.remove wire:target="submitApplication">Submit application</span>
                <span wire:loading wire:target="submitApplication" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" class="opacity-75"/></svg>
                    Sending…
                </span>
            </button>
        </form>
    @endif
</div>
