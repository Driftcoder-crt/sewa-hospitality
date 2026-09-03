<div class="admin-screen">
@section('title', 'Services — Sewa Admin')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">Services</h1>
            <p class="eyebrow mt-1 text-ink-muted">Catalog tree · slugs locked to the service catalog doc</p>
        </div>
        <button wire:click="create" type="button"
                class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">New service</button>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3">
        <select wire:model.live="status" aria-label="Filter by status"
                class="min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm text-ink">
            <option value="">All statuses</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-line bg-paper-2">
        <table class="w-full min-w-[760px] text-sm">
            <thead>
                <tr class="border-b border-line text-ink-muted">
                    <th class="px-4 py-3 text-start font-semibold">Service</th>
                    <th class="px-4 py-3 text-start font-semibold">Path</th>
                    <th class="px-4 py-3 text-start font-semibold">Lead tag</th>
                    <th class="px-4 py-3 text-start font-semibold">Status</th>
                    <th class="px-4 py-3 text-end font-semibold"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($services as $service)
                    <tr class="border-b border-line/60">
                        <td class="px-4 py-3">
                            <span class="font-medium text-ink">{{ str_repeat('↳ ', $service->parent_id ? 1 : 0) }}{{ $service->name }}</span>
                            <span class="ms-2 text-xs text-ink-muted">{{ $families[$service->family->value] ?? $service->family->value }}</span>
                        </td>
                        <td class="px-4 py-3"><code class="text-xs text-ink-soft">{{ $service->publicPath() }}</code></td>
                        <td class="px-4 py-3"><code class="text-xs text-ink-soft">{{ $service->lead_tag }}</code></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $service->status->isPublic() ? 'bg-success-500/15 text-ink' : ($service->status->value === 'archived' ? 'bg-paper-3 text-ink-muted' : 'bg-paper-3 text-ink-soft') }}">
                                {{ $service->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" wire:click="moveUp('{{ $service->getKey() }}')" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-ink-soft hover:bg-paper-3" aria-label="Move up">↑</button>
                                @if ($service->status->isPublic())
                                    <button type="button" wire:click="unpublish('{{ $service->getKey() }}')" class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">Archive</button>
                                @else
                                    <button type="button" wire:click="publish('{{ $service->getKey() }}')" class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">Publish</button>
                                @endif
                                <a href="{{ route('admin.services.edit', ['service' => $service->getKey()]) }}"
                                   class="inline-flex min-h-[36px] items-center rounded-lg bg-brand px-3 text-xs font-semibold text-brand-ink hover:opacity-90">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-ink-soft">No services match — run ServicesSeeder or create one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
