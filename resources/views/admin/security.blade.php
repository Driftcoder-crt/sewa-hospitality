<div class="admin-screen">
@section('title', 'Two-factor authentication — Sewa Admin')

    <div class="mx-auto max-w-xl">
        <h1 class="font-display text-2xl text-ink">Two-factor authentication</h1>
        <p class="eyebrow mt-1 text-ink-muted">Account security</p>

        <div class="mt-5 rounded-xl border border-line bg-paper-2 p-6">
            <p class="text-sm font-medium" role="status">
                @if ($twoFactorConfirmed)
                    <span class="text-success">✓ Two-factor authentication is enabled and confirmed.</span>
                @elseif ($hasTwoFactorSecret)
                    <span class="text-warning">A secret is present but not confirmed — finish enrolment below.</span>
                @else
                    <span class="text-danger">Not enrolled — this account has no authenticator yet.</span>
                @endif
            </p>

            <p class="mt-4 text-sm text-ink-soft">
                Run the command below on the server, then enter the one-time code from your
                authenticator app when prompted to confirm enrolment. The full enrolment UI
                lands with the System screens (M1).
            </p>

            <pre class="mt-3 overflow-x-auto rounded-lg bg-paper-3 p-3 text-sm text-ink">{{ $enableCommand }}</pre>

            <a href="{{ route('admin.dashboard') }}"
               class="mt-5 inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm font-medium text-ink">
                Back to dashboard
            </a>
        </div>
    </div>
</div>
