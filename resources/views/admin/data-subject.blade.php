<div class="admin-screen">
@section('title', 'Data subject tool — Sewa Admin')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">Data subject tool</h1>
            <p class="eyebrow mt-1 text-ink-muted">System · admin+ · DPDP right to access &amp; erasure</p>
        </div>
    </div>

    <div class="mt-4 rounded-xl border border-line bg-paper-2 p-4">
        <p class="text-sm text-ink-soft">
            Export everything the platform holds for one email address, or erase the subject's live
            leads and applications. <strong>Invoices, payments and the audit trail are never touched</strong>
            (legal retention wins). Every action here is audit-logged.
        </p>

        <div class="mt-4 flex flex-wrap items-end gap-3">
            <label class="block flex-1 text-sm sm:max-w-md">
                <span class="font-semibold text-ink-soft">Subject email</span>
                <input type="email" wire:model="email" placeholder="subject@example.com"
                       class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                @error('email') <span class="text-xs text-danger-500">{{ $message }}</span> @enderror
            </label>
            <button type="button" wire:click="export" wire:loading.attr="disabled"
                    class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink">
                Export JSON
            </button>
        </div>

        <div class="mt-4 rounded-lg border border-dashed border-line p-3">
            <label class="flex min-h-[44px] items-start gap-2 text-sm text-ink-soft">
                <input type="checkbox" wire:model="confirmErase" class="mt-1 h-4 w-4 rounded border-line">
                <span>I understand this anonymizes the subject's leads and applications in place and cannot be undone.</span>
            </label>
            @error('confirmErase') <span class="text-xs text-danger-500">{{ $message }}</span> @enderror
            <button type="button" wire:click="erase" wire:loading.attr="disabled"
                    class="mt-2 inline-flex min-h-[44px] items-center rounded-full border border-line px-5 text-sm font-semibold text-danger-500 hover:bg-paper-3">
                Erase subject data
            </button>
        </div>

        @if ($summary !== null)
            <div class="mt-4 rounded-lg bg-paper p-3 text-sm" role="status">
                <p class="font-semibold text-ink">{{ $lastAction === 'export' ? 'Export generated:' : 'Erasure complete:' }}</p>
                <ul class="mt-1 list-inside list-disc text-ink-soft">
                    @foreach ($summary as $section => $count)
                        <li>{{ str_replace('_', ' ', $section) }}: {{ $count }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
