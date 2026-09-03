<div class="admin-screen">
@section('title', 'Redirects — Sewa Admin')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">Redirects</h1>
            <p class="eyebrow mt-1 text-ink-muted">Content · admin+ (SEO equity)</p>
        </div>
        <button type="button" wire:click="$toggle('showForm')"
                class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">
            {{ $showForm ? 'Close form' : 'New redirect' }}
        </button>
    </div>

    @if ($showForm)
        <form wire:submit="{{ $editingId ? 'update' : 'create' }}" class="mt-4 rounded-xl border border-line bg-paper-2 p-4">
            <div class="grid gap-3 md:grid-cols-2">
                <label class="block text-sm">
                    <span class="font-semibold text-ink-soft">From (path)</span>
                    <input type="text" wire:model="from" placeholder="/old-page"
                           class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                    @error('from') <span class="mt-1 block text-xs text-danger-500">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm">
                    <span class="font-semibold text-ink-soft">To (path or URL)</span>
                    <input type="text" wire:model="to" placeholder="/new-page"
                           class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                    @error('to') <span class="mt-1 block text-xs text-danger-500">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm">
                    <span class="font-semibold text-ink-soft">Code</span>
                    <select wire:model="code" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                        @foreach ($codes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="font-semibold text-ink-soft">Note (internal)</span>
                    <input type="text" wire:model="note"
                           class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                </label>
            </div>
            <div class="mt-3 flex items-center gap-4">
                <label class="flex min-h-[44px] items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="active" class="h-4 w-4 rounded border-line"> Active
                </label>
                <button type="submit" class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink">
                    {{ $editingId ? 'Update redirect' : 'Create redirect' }}
                </button>
                @if ($editingId)
                    <button type="button" wire:click="$set('showForm', false)" class="text-sm text-ink-soft hover:text-ink">Cancel</button>
                @endif
            </div>
        </form>
    @endif

    <form wire:submit="importCsv" class="mt-4 rounded-xl border border-dashed border-line p-4">
        <p class="text-sm font-semibold text-ink">CSV import</p>
        <p class="mt-1 text-xs text-ink-muted">Columns: <code>from,to,code</code> — code optional (default 301). Existing paths update in place.</p>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <input type="file" wire:model="csv" accept=".csv,text/csv"
                   class="block w-full max-w-sm text-sm text-ink-soft file:me-3 file:rounded-lg file:border-0 file:bg-paper-3 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-ink">
            <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm font-semibold text-ink hover:bg-paper-3"
                    wire:loading.attr="disabled">Import</button>
            @error('csv') <span class="text-xs text-danger-500">{{ $message }}</span> @enderror
        </div>
    </form>

    <div class="mt-4 overflow-x-auto rounded-xl border border-line bg-paper-2">
        <table class="w-full min-w-[720px] text-sm">
            <thead>
                <tr class="border-b border-line text-ink-muted">
                    <th class="px-4 py-3 text-start font-semibold">From</th>
                    <th class="px-4 py-3 text-start font-semibold">To</th>
                    <th class="px-4 py-3 text-start font-semibold">Code</th>
                    <th class="px-4 py-3 text-start font-semibold">Hits</th>
                    <th class="px-4 py-3 text-start font-semibold">Status</th>
                    <th class="px-4 py-3 text-end font-semibold"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($redirects as $redirect)
                    <tr class="border-b border-line/60">
                        <td class="px-4 py-3"><code class="text-xs text-ink">{{ $redirect->from }}</code></td>
                        <td class="px-4 py-3"><code class="text-xs text-ink-soft">{{ $redirect->to }}</code></td>
                        <td class="px-4 py-3 text-xs text-ink-soft">{{ $redirect->code->value }}</td>
                        <td class="px-4 py-3 font-display text-base text-ink">{{ number_format($redirect->hits) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $redirect->active ? 'bg-success-500/15 text-ink' : 'bg-paper-3 text-ink-muted' }}">
                                {{ $redirect->active ? 'Active' : 'Off' }}
                            </span>
                            @if ($redirect->note)
                                <span class="ms-1 text-xs text-ink-muted">{{ $redirect->note }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" wire:click="edit('{{ $redirect->getKey() }}')"
                                        class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">Edit</button>
                                <button type="button" wire:click="toggle('{{ $redirect->getKey() }}')"
                                        class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">{{ $redirect->active ? 'Disable' : 'Enable' }}</button>
                                <button type="button" wire:click="delete('{{ $redirect->getKey() }}')" wire:confirm="Delete this redirect?"
                                        class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-danger-500/10">Delete</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center">
                            <p class="font-display text-lg text-ink">No redirects yet</p>
                            <p class="mt-1 text-sm text-ink-soft">Slug moves in the page editor offer 301s automatically; add manual rules above.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $redirects->links() }}</div>
</div>
