<div class="admin-screen">
@section('title', 'Languages & translations — Sewa Admin')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">Languages &amp; translations</h1>
            <p class="eyebrow mt-1 text-ink-muted">I18n · editor+ · machine drafts never publish without review</p>
        </div>
    </div>

    <nav class="mt-4 flex flex-wrap gap-2" aria-label="I18n sections">
        @foreach (['locales' => 'Locales', 'strings' => 'UI strings', 'content' => 'Content queue'] as $key => $label)
            <button type="button" wire:click="switchTab('{{ $key }}')"
                    @if ($tab === $key) aria-current="page" @endif
                    class="inline-flex min-h-[44px] items-center rounded-full px-4 text-sm font-semibold {{ $tab === $key ? 'bg-brand text-brand-ink' : 'border border-line text-ink-soft hover:bg-paper-3' }}">
                {{ $label }}
            </button>
        @endforeach
    </nav>

    @if ($tab === 'locales')
        <div class="mt-4 overflow-x-auto rounded-xl border border-line bg-paper-2">
            <table class="w-full min-w-[720px] text-sm">
                <thead>
                    <tr class="border-b border-line text-ink-muted">
                        <th class="px-4 py-3 text-start font-semibold">Code</th>
                        <th class="px-4 py-3 text-start font-semibold">Name</th>
                        <th class="px-4 py-3 text-start font-semibold">Native</th>
                        <th class="px-4 py-3 text-start font-semibold">Direction</th>
                        <th class="px-4 py-3 text-start font-semibold">Serving</th>
                        <th class="px-4 py-3 text-start font-semibold">Auto-translate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($locales as $locale)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-3 font-semibold text-ink">{{ $locale->code }}</td>
                            <td class="px-4 py-3 text-ink-soft">{{ $locale->name }}</td>
                            <td class="px-4 py-3 text-ink-soft">{{ $locale->native_name }}</td>
                            <td class="px-4 py-3 text-ink-soft">{{ strtoupper($locale->direction) }}</td>
                            <td class="px-4 py-3">
                                <button type="button" wire:click="toggleLocale('{{ $locale->code }}')" wire:loading.attr="disabled"
                                        class="inline-flex min-h-[36px] items-center rounded-full px-3 text-xs font-semibold {{ $locale->enabled ? 'bg-brand/10 text-brand' : 'bg-paper-3 text-ink-muted' }}">
                                    {{ $locale->enabled ? 'Enabled' : 'Disabled' }}
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                @if ($locale->isDefault())
                                    <span class="text-xs text-ink-muted">source (never)</span>
                                @else
                                    <button type="button" wire:click="toggleAutoTranslate('{{ $locale->code }}')" wire:loading.attr="disabled"
                                            class="inline-flex min-h-[36px] items-center rounded-full px-3 text-xs font-semibold {{ $locale->auto_translate ? 'bg-brand/10 text-brand' : 'bg-paper-3 text-ink-muted' }}">
                                        {{ $locale->auto_translate ? 'On' : 'Off' }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-2 text-xs text-ink-muted">Toggling a locale off removes it from routes, hreflang and the switcher immediately; machine drafts are kept.</p>
    @elseif ($tab === 'strings')
        <form wire:submit.prevent class="mt-4 flex flex-wrap items-end gap-3 rounded-xl border border-line bg-paper-2 p-4">
            <label class="block text-sm">
                <span class="font-semibold text-ink-soft">Locale</span>
                <select wire:model="stringLocale" class="mt-1 min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                    @foreach ($switcherLocales as $code => $native)
                        <option value="{{ $code }}">{{ $native }} ({{ $code }})</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="font-semibold text-ink-soft">Namespace</span>
                <select wire:model="stringNamespace" class="mt-1 min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                    @foreach ($namespaces as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="font-semibold text-ink-soft">Status</span>
                <select wire:model="stringStatus" class="mt-1 min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                    <option value="">All</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </form>

        <div class="mt-4 flex flex-col gap-3">
            @forelse ($strings as $string)
                <article class="rounded-xl border border-line bg-paper-2 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink">{{ $string->key }}</p>
                            <p class="mt-0.5 text-xs text-ink-muted">
                                {{ $string->namespace }} · {{ $string->locale }} ·
                                <span class="{{ $string->status === 'human-reviewed' ? 'text-brand' : 'text-warning' }}">{{ $string->status->label() }}</span>
                                @if ($string->reviewer)
                                    · reviewed by {{ $string->reviewer->name }}
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($string->status->isPublishable())
                                <button type="button" wire:click="reject('{{ $string->id }}')" wire:loading.attr="disabled"
                                        class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">
                                    Revert to machine
                                </button>
                            @else
                                <button type="button" wire:click="approve('{{ $string->id }}')" wire:loading.attr="disabled"
                                        class="inline-flex min-h-[36px] items-center rounded-lg bg-brand px-3 text-xs font-semibold text-brand-ink">
                                    Approve
                                </button>
                            @endif
                            <button type="button" wire:click="editString('{{ $string->id }}')"
                                    class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink hover:bg-paper-3">
                                {{ $string->status->isPublishable() ? 'Edit' : 'Edit & approve' }}
                            </button>
                        </div>
                    </div>

                    @if ($editingStringId === $string->id)
                        <div class="mt-3">
                            <textarea wire:model="editingValue" rows="3"
                                      class="w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm text-ink"></textarea>
                            @error('editingValue') <span class="text-xs text-danger-500">{{ $message }}</span> @enderror
                            <div class="mt-2 flex gap-2">
                                <button type="button" wire:click="approveEdited" wire:loading.attr="disabled"
                                        class="inline-flex min-h-[44px] items-center rounded-lg bg-brand px-4 text-sm font-semibold text-brand-ink">
                                    Save &amp; approve
                                </button>
                                <button type="button" wire:click="cancelEdit" class="inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm text-ink-soft hover:bg-paper-3">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    @else
                        <p class="mt-2 whitespace-pre-wrap rounded-lg bg-paper px-3 py-2 text-sm text-ink-soft">{{ $string->value }}</p>
                    @endif
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">
                    No strings for this filter — machine-seeded strings appear here for review.
                </div>
            @endforelse
        </div>

        <div class="mt-4">{{ $strings->links() }}</div>
    @else
        <div class="mt-4 flex flex-col gap-3">
            @forelse ($drafts as $row)
                <article class="rounded-xl border border-line bg-paper-2 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink">
                                {{ $row['label'] }} · {{ $row['variant']->locale }}
                                @if ($row['variant']->locale === 'ar') <span class="text-xs text-ink-muted">(RTL)</span> @endif
                            </p>
                            <p class="mt-0.5 text-xs text-ink-muted">
                                machine draft of <code>{{ $row['variant']->locale_source_id }}</code>
                                @if ($row['source'])
                                    — source: {{ \Illuminate\Support\Str::limit((string) ($row['source']->title ?? $row['source']->name ?? $row['source']->slug ?? ''), 60) }}
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="requeue('{{ $row['class'] }}', '{{ $row['variant']->locale_source_id }}', '{{ $row['variant']->locale }}')"
                                    wire:loading.attr="disabled"
                                    class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink hover:bg-paper-3">
                                Requeue
                            </button>
                            <button type="button" wire:click="discard('{{ $row['class'] }}', '{{ $row['variant']->id }}')"
                                    wire:loading.attr="disabled"
                                    wire:confirm="Discard this machine draft? The EN source keeps serving."
                                    class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-danger-500 hover:bg-paper-3">
                                Discard
                            </button>
                        </div>
                    </div>
                    @php($preview = \Illuminate\Support\Str::limit((string) ($row['variant']->description ?? $row['variant']->short_desc ?? $row['variant']->title ?? $row['variant']->name ?? ''), 300))
                    <p @if ($row['variant']->locale === 'ar') dir="rtl" @endif
                       class="mt-2 whitespace-pre-wrap rounded-lg bg-paper px-3 py-2 text-sm text-ink-soft">{{ $preview }}</p>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">
                    No machine drafts waiting — published EN content is queued automatically (queue: ai).
                </div>
            @endforelse
        </div>

        <form wire:submit="dispatchManual" class="mt-6 rounded-xl border border-dashed border-line p-4">
            <p class="text-sm font-semibold text-ink">Manual dispatch</p>
            <p class="mt-1 text-xs text-ink-muted">Queue machine-draft translations for an entity that predates the pipeline. One job per enabled auto-translate locale.</p>
            <div class="mt-3 flex flex-wrap items-end gap-3">
                <label class="block text-sm">
                    <span class="font-semibold text-ink-soft">Entity</span>
                    <select wire:model="manualEntity" class="mt-1 min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                        @foreach ($entityOptions as $class => $label)
                            <option value="{{ $class }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="font-semibold text-ink-soft">Entity id (ULID)</span>
                    <input type="text" wire:model="manualId" placeholder="01J…"
                           class="mt-1 min-h-[44px] w-64 rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                    @error('manualId') <span class="text-xs text-danger-500">{{ $message }}</span> @enderror
                </label>
                <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink">
                    Queue translations
                </button>
            </div>
        </form>
    @endif
</div>
