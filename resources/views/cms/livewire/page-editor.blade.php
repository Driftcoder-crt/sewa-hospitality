<div class="admin-screen">
@section('title', 'Edit page — Sewa Admin')

@php
    $fieldCount = function (array $definition): int {
        return count($definition['fields'] ?? []);
    };
@endphp

    {{-- Editor state header: autosave banner lives here (never silent loss, cms §6). --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">{{ $page->slug === 'home' ? 'Home page' : $page->title }}</h1>
            <p class="eyebrow mt-1 text-ink-muted">
                Content · Pages · {{ $statuses[$page->status->value] ?? ucfirst($page->status->value) }}
                @if ($page->status->isPublic()) · <a href="{{ $page->publicPath() }}" target="_blank" rel="noopener" class="underline">view live</a> @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
               class="inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm font-semibold text-ink-soft hover:bg-paper-3">Preview</a>
            <button type="button" wire:click="toggleRevisions"
                    class="inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm font-semibold text-ink-soft hover:bg-paper-3"
                    :aria-pressed="showRevisions ? 'true' : 'false'">
                Revisions
            </button>
            <button type="button" wire:click="save" wire:loading.attr="disabled"
                    class="inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm font-semibold text-ink hover:bg-paper-3">Save</button>
            <button type="button" wire:click="publish"
                    class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">
                {{ $page->status->isPublic() ? 'Update live' : 'Publish' }}
            </button>
            @if ($page->status->isPublic())
                <button type="button" wire:click="unpublish" wire:confirm="Unpublish this page?"
                        class="inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm font-semibold text-ink-soft hover:bg-paper-3">Unpublish</button>
            @endif
        </div>
    </div>

    {{-- Autosave status banner --}}
    <div class="mt-3" aria-live="polite">
        @if ($autosaveState === 'error')
            <div class="flex items-center justify-between gap-3 rounded-xl border border-danger-500/40 bg-danger-500/10 p-4 text-sm">
                <span class="text-ink">{{ $autosaveError ?: 'Unsaved changes could not be saved.' }}</span>
                <button type="button" wire:click="save" class="inline-flex min-h-[36px] items-center rounded-lg border border-line bg-paper px-3 text-xs font-semibold">Retry now</button>
            </div>
        @elseif ($autosaveState === 'dirty')
            <p class="rounded-xl border border-warning-500/40 bg-warning-500/10 p-3 text-sm text-ink">Unsaved changes — autosaving every 10 s…</p>
        @elseif ($autosaveState === 'saving')
            <p class="rounded-xl border border-line bg-paper-2 p-3 text-sm text-ink-soft" role="status">Saving…</p>
        @elseif ($autosaveState === 'saved')
            <p class="rounded-xl border border-success-500/40 bg-success-500/10 p-3 text-sm text-ink" role="status">All changes saved.</p>
        @endif
    </div>

    {{-- Publish gate errors (04-modules/01-cms.md §5 field-level) --}}
    @if ($gateErrors !== [])
        <div class="mt-3 rounded-xl border border-danger-500/40 bg-danger-500/10 p-4" role="alert">
            <p class="text-sm font-semibold text-ink">Publish blocked</p>
            <ul class="mt-2 list-disc space-y-1 ps-5 text-sm text-ink-soft">
                @foreach ($gateErrors as $field => $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        {{-- Block canvas --}}
        <div class="lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-lg">Blocks</h2>
                <span class="text-xs text-ink-muted">Lead block renders the H1</span>
            </div>

            <div class="mt-3 flex flex-col gap-3">
                @foreach ($blocks as $i => $block)
                    @php($definition = $definitions[$block['type']] ?? null)
                    <article class="rounded-xl border border-line bg-paper-2">
                        <header class="flex items-center justify-between gap-2 border-b border-line px-4 py-3">
                            <p class="text-sm font-semibold text-ink">
                                {{ $definition ? $definition['code'].' · '.$definition['label'] : ucfirst(str_replace('_',' ',$block['type'])) }}
                                <span class="ms-2 text-xs font-normal text-ink-muted">{{ $i === 0 ? 'lead' : 'block '.($i + 1) }}</span>
                            </p>
                            <div class="flex items-center gap-1">
                                <button type="button" wire:click="moveBlockUp({{ $i }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-ink-soft hover:bg-paper-3" aria-label="Move up">↑</button>
                                <button type="button" wire:click="moveBlockDown({{ $i }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-ink-soft hover:bg-paper-3" aria-label="Move down">↓</button>
                                <button type="button" wire:click="removeBlock({{ $i }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-ink-soft hover:bg-danger-500/10 hover:text-ink" aria-label="Remove block">✕</button>
                            </div>
                        </header>

                        <div class="grid gap-3 p-4 sm:grid-cols-2">
                            @if ($definition)
                                @foreach ($definition['fields'] as $key => $field)
                                    @php($path = "blocks.{$i}.data.{$key}")
                                    <div class="{{ in_array($field['type'], ['textarea','html','items','ctas']) ? 'sm:col-span-2' : '' }}">
                                        @if ($field['type'] === 'boolean')
                                            <label class="flex min-h-[44px] items-center gap-2 text-sm text-ink">
                                                <input type="checkbox" wire:model="{{ $path }}" class="h-4 w-4 rounded border-line">
                                                {{ $field['label'] }}
                                            </label>
                                        @elseif ($field['type'] === 'select')
                                            <label class="block text-xs font-semibold text-ink-soft" for="{{ $path }}">{{ $field['label'] }}</label>
                                            <select id="{{ $path }}" wire:model="{{ $path }}"
                                                    class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                                                @foreach ($field['options'] as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @elseif ($field['type'] === 'items' || $field['type'] === 'ctas')
                                            <p class="text-xs font-semibold text-ink-soft">{{ $field['label'] }}</p>
                                            @foreach ($block['data'][$key] ?? [] as $itemIndex => $item)
                                                <div class="mt-2 rounded-lg border border-line bg-paper p-3">
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-xs text-ink-muted">Item {{ $itemIndex + 1 }}</span>
                                                        <button type="button" wire:click="removeItem({{ $i }}, '{{ $key }}', {{ $itemIndex }})"
                                                                class="text-xs text-ink-muted hover:text-ink">remove</button>
                                                    </div>
                                                    @foreach (($field['item_fields'] ?? ($field['type'] === 'ctas' ? [
                                                        'label' => ['label' => 'Label', 'type' => 'text'],
                                                        'url' => ['label' => 'URL', 'type' => 'text'],
                                                        'variant' => ['label' => 'Variant', 'type' => 'text'],
                                                    ] : [])) as $subKey => $subField)
                                                        <div class="mt-2">
                                                            <label class="block text-xs text-ink-soft" for="{{ $path }}.{{ $itemIndex }}.{{ $subKey }}">{{ $subField['label'] }}</label>
                                                            @if (($subField['type'] ?? 'text') === 'textarea' || ($subField['type'] ?? '') === 'html')
                                                                <textarea id="{{ $path }}.{{ $itemIndex }}.{{ $subKey }}" wire:model="{{ $path }}.{{ $itemIndex }}.{{ $subKey }}" rows="3"
                                                                          class="mt-1 w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm text-ink"></textarea>
                                                            @else
                                                                <input type="text" id="{{ $path }}.{{ $itemIndex }}.{{ $subKey }}" wire:model="{{ $path }}.{{ $itemIndex }}.{{ $subKey }}"
                                                                       class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                            <button type="button" wire:click="addItem({{ $i }}, '{{ $key }}')"
                                                    class="mt-2 inline-flex min-h-[36px] items-center rounded-lg border border-dashed border-line px-3 text-xs font-semibold text-ink-soft">+ Add item</button>
                                        @elseif ($field['type'] === 'textarea' || $field['type'] === 'html')
                                            <label class="block text-xs font-semibold text-ink-soft" for="{{ $path }}">{{ $field['label'] }} @if ($field['type'] === 'html')<span class="font-normal text-ink-muted">(sanitized)</span>@endif</label>
                                            <textarea id="{{ $path }}" wire:model="{{ $path }}" rows="{{ $field['type'] === 'html' ? 6 : 3 }}"
                                                      class="mt-1 w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm text-ink"></textarea>
                                            @if (isset($field['max']))
                                                <p class="mt-1 text-xs text-ink-muted">{{ str($block['data'][$key] ?? '')->length() }}/{{ $field['max'] }}</p>
                                            @endif
                                        @else
                                            <label class="block text-xs font-semibold text-ink-soft" for="{{ $path }}">{{ $field['label'] }} @if ($field['required'] ?? false)<span class="text-danger-500">*</span>@endif</label>
                                            <input type="text" id="{{ $path }}" wire:model="{{ $path }}"
                                                   class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <p class="text-sm text-danger-500 sm:col-span-2">Unknown block type — remove this block to publish.</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Add block picker --}}
            <div class="mt-4 rounded-xl border border-dashed border-line p-4">
                <p class="text-sm font-semibold text-ink">Add a block</p>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    @foreach ($registry as $category => $types)
                        <div>
                            <p class="eyebrow text-ink-muted">{{ $category }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($types as $type => $label)
                                    <button type="button" wire:click="addBlock('{{ $type }}')"
                                            class="inline-flex min-h-[36px] items-center rounded-full border border-line bg-paper px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">
                                        + {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Settings drawer --}}
        <aside class="flex flex-col gap-4">
            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <h2 class="font-display text-lg">Settings</h2>

                <div class="mt-3 grid gap-3">
                    <label class="block text-sm" for="title">
                        <span class="font-semibold text-ink-soft">Title</span>
                        <input type="text" id="title" wire:model.live.debounce.300ms="title"
                               class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                    </label>

                    <label class="block text-sm" for="slug">
                        <span class="font-semibold text-ink-soft">Slug</span>
                        <input type="text" id="slug" wire:model.live.debounce.300ms="slug"
                               class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                        <span class="mt-1 block text-xs text-ink-muted">Public path: <code>{{ $page->slug === 'home' ? '/' : ($type === 'legal' ? '/legal/' : ($type === 'landing' ? '/p/' : '/')) }}{{ $slug }}</code></span>
                    </label>

                    <label class="block text-sm" for="type">
                        <span class="font-semibold text-ink-soft">Type</span>
                        <select id="type" wire:model.live="type"
                                class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    @if ($page->status->value === 'scheduled')
                        <label class="block text-sm" for="scheduled_at">
                            <span class="font-semibold text-ink-soft">Scheduled for</span>
                            <input type="datetime-local" id="scheduled_at" wire:model="scheduled_at"
                                   class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                        </label>
                    @endif

                    @if ($slugChanged)
                        <label class="flex min-h-[44px] items-start gap-2 rounded-lg border border-warning-500/40 bg-warning-500/10 p-3 text-sm">
                            <input type="checkbox" wire:model="addRedirect" class="mt-1 h-4 w-4 rounded border-line">
                            <span class="text-ink">Add a 301 redirect from <code>{{ $originalSlug }}</code> — recommended (SEO equity follows the move).</span>
                        </label>
                    @endif
                </div>
            </div>

            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <h2 class="font-display text-lg">SEO</h2>
                <div class="mt-3 grid gap-3">
                    <label class="block text-sm" for="meta_title">
                        <span class="font-semibold text-ink-soft">Meta title</span>
                        <input type="text" id="meta_title" wire:model.live.debounce.300ms="meta_title"
                               class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                        <span class="mt-1 block text-xs {{ str($meta_title)->length() > 60 ? 'text-danger-500' : 'text-ink-muted' }}">{{ str($meta_title)->length() }}/60 — required to publish</span>
                    </label>
                    <label class="block text-sm" for="meta_description">
                        <span class="font-semibold text-ink-soft">Meta description</span>
                        <textarea id="meta_description" wire:model.live.debounce.300ms="meta_description" rows="3"
                                  class="mt-1 w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm text-ink"></textarea>
                        <span class="mt-1 block text-xs {{ str($meta_description)->length() > 160 ? 'text-danger-500' : 'text-ink-muted' }}">{{ str($meta_description)->length() }}/160 — required to publish</span>
                    </label>
                    <label class="block text-sm" for="canonical_override">
                        <span class="font-semibold text-ink-soft">Canonical override <span class="font-normal text-ink-muted">(optional)</span></span>
                        <input type="url" id="canonical_override" wire:model="canonical_override"
                               class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                    </label>

                    <div class="rounded-lg border border-line bg-paper p-3">
                        <label class="flex min-h-[44px] items-center gap-2 text-sm">
                            <input type="checkbox" wire:model.live="noindex" class="h-4 w-4 rounded border-line">
                            <span class="font-semibold text-ink">noindex this page</span>
                        </label>
                        @if ($noindex)
                            <label class="mt-2 block text-sm" for="noindex_reason">
                                <span class="font-semibold text-ink-soft">Reason (required, logged)</span>
                                <textarea id="noindex_reason" wire:model="noindex_reason" rows="2"
                                          class="mt-1 w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm text-ink"></textarea>
                            </label>
                            @if (! $noindex_confirmed)
                                <button type="button" wire:click="$set('noindex_confirmed', true)"
                                        class="mt-2 inline-flex min-h-[36px] items-center rounded-lg border border-danger-500/40 bg-danger-500/10 px-3 text-xs font-semibold text-ink">
                                    Confirm noindex — I understand this removes the page from search
                                </button>
                            @else
                                <p class="mt-2 text-xs text-ink-soft" role="status">Confirmed{{ $page->noindex_confirmed_at ? ' on '.$page->noindex_confirmed_at->format('d M Y') : '' }} — saved on next save.</p>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            {{-- Revisions --}}
            @if ($showRevisions)
                <div class="rounded-xl border border-line bg-paper-2 p-4">
                    <h2 class="font-display text-lg">Revisions</h2>
                    <p class="mt-1 text-xs text-ink-muted">Last {{ \App\Modules\Cms\Services\RevisionManager::CAP }} kept · restoring records a new revision.</p>
                    <ul class="mt-3 flex max-h-96 flex-col gap-1 overflow-y-auto">
                        @forelse ($revisions as $revision)
                            <li class="rounded-lg border border-line bg-paper">
                                <div class="flex items-center justify-between gap-2 p-3">
                                    <label class="flex min-w-0 items-center gap-2 text-sm">
                                        <input type="radio" name="diffRevisionId" value="{{ $revision->getKey() }}" wire:model.live="diffRevisionId" class="h-4 w-4">
                                        <span class="min-w-0">
                                            <span class="block truncate text-ink">{{ $revision->created_at?->format('d M Y H:i') }}</span>
                                            <span class="block text-xs text-ink-muted">{{ $revision->created_at?->diffForHumans() }}</span>
                                        </span>
                                    </label>
                                    <button type="button" wire:click="restoreRevision('{{ $revision->getKey() }}')" wire:confirm="Restore this revision? (Records a new revision.)"
                                            class="inline-flex min-h-[36px] shrink-0 items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">Restore</button>
                                </div>

                                @if ($diffRevisionId === $revision->getKey() && $diff !== null)
                                    <div class="border-t border-line p-3 text-xs">
                                        @if ($diff['added'] !== [] || $diff['removed'] !== [])
                                            <p class="mt-1">
                                                @foreach ($diff['added'] as $addedLabel)
                                                    <span class="me-1 inline-flex rounded bg-success-500/15 px-1.5 py-0.5 font-semibold text-ink">+ {{ $addedLabel }}</span>
                                                @endforeach
                                                @foreach ($diff['removed'] as $removedLabel)
                                                    <span class="me-1 inline-flex rounded bg-danger-500/15 px-1.5 py-0.5 font-semibold text-ink line-through">− {{ $removedLabel }}</span>
                                                @endforeach
                                            </p>
                                        @endif
                                        @foreach ($diff['changes'] as $change)
                                            <div class="mt-2 rounded border border-line p-2">
                                                <p class="font-semibold text-ink">{{ $change['label'] }}</p>
                                                @foreach ($change['fields'] as $fieldDiff)
                                                    <p class="mt-1 text-ink-soft">
                                                        <span class="font-mono">{{ $fieldDiff['field'] }}</span>:
                                                        @foreach ($fieldDiff['ops'] as $op)
                                                            @if ($op['op'] === 'add') <ins class="bg-success-500/15 text-ink no-underline">{{ $op['text'] }}</ins>
                                                            @elseif ($op['op'] === 'del') <del class="bg-danger-500/15 text-ink">{{ $op['text'] }}</del>
                                                            @else {{ $op['text'] }} @endif
                                                        @endforeach
                                                    </p>
                                                @endforeach
                                            </div>
                                        @endforeach
                                        @if ($diff['added'] === [] && $diff['removed'] === [] && $diff['changes'] === [])
                                            <p class="mt-2 text-ink-muted">No differences.</p>
                                        @endif
                                    </div>
                                @endif
                            </li>
                        @empty
                            <li class="px-3 py-4 text-center text-sm text-ink-muted">No revisions yet — save to create the first.</li>
                        @endforelse
                    </ul>
                </div>
            @endif
        </aside>
    </div>
</div>
