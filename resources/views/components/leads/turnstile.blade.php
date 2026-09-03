{{-- Turnstile widget (error lock #3) — renders the Cloudflare widget
     ONLY when a site key is configured; local/test runs skip silently
     (TurnstileVerifier skips too, so the pair stays consistent).
     The widget callback mirrors the token into the Livewire-bound
     hidden input via an `input` event. --}}
@php
    $uid = 'ts-'.uniqid();
    $siteKey = (string) config('sewa.turnstile.site_key');
@endphp

@if ($siteKey !== '')
    <div class="flex flex-col gap-1.5">
        <div class="cf-turnstile"
             data-sitekey="{{ $siteKey }}"
             data-theme="light"
             data-callback="sewaTurnstileCallback"
             aria-label="Spam verification"></div>

        <input type="hidden" wire:model="turnstileToken"
               data-turnstile-input="{{ $uid }}"
               x-ref="turnstileInput">
    </div>

    @once
        @push('head')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            <script>
                window.sewaTurnstileCallback = function (token) {
                    document.querySelectorAll('[data-turnstile-input]').forEach(function (input) {
                        input.value = token;
                        input.dispatchEvent(new Event('input'));
                    });
                };
            </script>
        @endpush
    @endonce
@endif
