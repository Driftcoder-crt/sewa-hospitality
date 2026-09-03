<div class="admin-screen">
@section('title', 'Edit service — Sewa Admin')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">{{ $service->name }}</h1>
            <p class="eyebrow mt-1 text-ink-muted">
                Services · {{ $service->publicPath() }} · {{ $service->status->label() }}
                @if ($service->status->isPublic()) · <a href="{{ $service->publicPath() }}" target="_blank" rel="noopener" class="underline">view live</a> @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="save" class="inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm font-semibold text-ink hover:bg-paper-3">Save</button>
            @if ($canPublish)
                @if ($service->status->isPublic())
                    <button type="button" wire:click="unpublish" wire:confirm="Archive this service?" class="inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm font-semibold text-ink-soft hover:bg-paper-3">Archive</button>
                @else
                    <button type="button" wire:click="publish" class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">Publish</button>
                @endif
            @endif
        </div>
    </div>

    <div class="mt-3" aria-live="polite">
        @if ($autosaveState === 'error')
            <div class="flex items-center justify-between gap-3 rounded-xl border border-danger-500/40 bg-danger-500/10 p-4 text-sm">
                <span class="text-ink">{{ $autosaveError ?: 'Unsaved changes could not be saved.' }}</span>
                <button type="button" wire:click="save" class="inline-flex min-h-[36px] items-center rounded-lg border border-line bg-paper px-3 text-xs font-semibold">Retry now</button>
            </div>
        @elseif ($autosaveState === 'dirty')
            <p class="rounded-xl border border-warning-500/40 bg-warning-500/10 p-3 text-sm text-ink">Unsaved changes — autosaving every 10 s…</p>
        @elseif ($autosaveState === 'saved')
            <p class="rounded-xl border border-success-500/40 bg-success-500/10 p-3 text-sm text-ink" role="status">All changes saved.</p>
        @endif
    </div>

    @if ($gateErrors !== [])
        <div class="mt-3 rounded-xl border border-danger-500/40 bg-danger-500/10 p-4" role="alert">
            <p class="text-sm font-semibold text-ink">Publish blocked (meta + intro + hero media + ≥1 block are required)</p>
            <ul class="mt-2 list-disc space-y-1 ps-5 text-sm text-ink-soft">
                @foreach ($gateErrors as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        {{-- Block canvas (same library as CMS pages) --}}
        <div class="lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-lg">Content blocks</h2>
                <span class="text-xs text-ink-muted">Same library as pages</span>
            </div>

            <div class="mt-3 flex flex-col gap-3">
                @foreach ($blocks as $i => $block)
                    @php($definition = $definitions[$block['type']] ?? null)
                    <article class="rounded-xl border border-line bg-paper-2">
                        <header class="flex items-center justify-between gap-2 border-b border-line px-4 py-3">
                            <p class="text-sm font-semibold text-ink">{{ $definition ? $definition['code'].' · '.$definition['label'] : ucfirst(str_replace('_',' ',$block['type'])) }}</p>
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
                                                <input type="checkbox" wire:model="{{ $path }}" class="h-4 w-4 rounded border-line"> {{ $field['label'] }}
                                            </label>
                                        @elseif ($field['type'] === 'select')
                                            <label class="block text-xs font-semibold text-ink-soft" for="{{ $path }}">{{ $field['label'] }}</label>
                                            <select id="{{ $path }}" wire:model="{{ $path }}" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                                                @foreach ($field['options'] as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @elseif ($field['type'] === 'textarea' || $field['type'] === 'html')
                                            <label class="block text-xs font-semibold text-ink-soft" for="{{ $path }}">{{ $field['label'] }}</label>
                                            <textarea id="{{ $path }}" wire:model="{{ $path }}" rows="{{ $field['type'] === 'html' ? 6 : 3 }}" class="mt-1 w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm text-ink"></textarea>
                                        @elseif (in_array($field['type'], ['items','ctas']))
                                            <p class="text-xs font-semibold text-ink-soft">{{ $field['label'] }} — edit via page editor for repeaters ({{ count($block['data'][$key] ?? []) }} items)</p>
                                        @else
                                            <label class="block text-xs font-semibold text-ink-soft" for="{{ $path }}">{{ $field['label'] }}</label>
                                            <input type="text" id="{{ $path }}" wire:model="{{ $path }}" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <p class="text-sm text-danger-500 sm:col-span-2">Unknown block type — remove this block.</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-4 rounded-xl border border-dashed border-line p-4">
                <p class="text-sm font-semibold text-ink">Add a block</p>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    @foreach ($registry as $category => $types)
                        <div>
                            <p class="eyebrow text-ink-muted">{{ $category }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($types as $type => $label)
                                    <button type="button" wire:click="addBlock('{{ $type }}')"
                                            class="inline-flex min-h-[36px] items-center rounded-full border border-line bg-paper px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">+ {{ $label }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Service drawer --}}
        <aside class="flex flex-col gap-4">
            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <h2 class="font-display text-lg">Service</h2>
                <div class="mt-3 grid gap-3">
                    <label class="block text-sm"><span class="font-semibold text-ink-soft">Name</span>
                        <input type="text" wire:model.live.debounce.300ms="name" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink"></label>
                    <label class="block text-sm"><span class="font-semibold text-ink-soft">Slug (catalog-locked)</span>
                        <input type="text" wire:model.live.debounce.300ms="slug" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink"></label>
                    <label class="block text-sm"><span class="font-semibold text-ink-soft">Family</span>
                        <select wire:model.live="family" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                            @foreach ($families as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select></label>
                    <label class="block text-sm"><span class="font-semibold text-ink-soft">Parent (empty = family hub)</span>
                        <select wire:model="parent_id" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                            <option value="">— none (family hub) —</option>
                            @foreach ($parents as $parent)
                                <option value="{{ $parent->getKey() }}">{{ $parent->name }}</option>
                            @endforeach
                        </select></label>
                    <label class="block text-sm"><span class="font-semibold text-ink-soft">Icon key</span>
                        <input type="text" wire:model.live.debounce.300ms="icon_svg_key" placeholder="plane, building, home…"
                               class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink"></label>
                    <label class="block text-sm"><span class="font-semibold text-ink-soft">CTA label override</span>
                        <input type="text" wire:model.live.debounce.300ms="cta_label_override" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink"></label>
                    <label class="block text-sm">
                        <span class="font-semibold text-ink-soft">Lead tag @if (! $canLeadTag)<span class="font-normal text-ink-muted">(admin+ only)</span>@endif</span>
                        <input type="text" wire:model.live.debounce.300ms="lead_tag" @if (! $canLeadTag) disabled @endif
                               class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink disabled:opacity-50">
                    </label>
                </div>
            </div>

            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <h2 class="font-display text-lg">SEO</h2>
                <div class="mt-3 grid gap-3">
                    <label class="block text-sm"><span class="font-semibold text-ink-soft">Meta title</span>
                        <input type="text" wire:model.live.debounce.300ms="meta_title" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                        <span class="mt-1 block text-xs {{ str($meta_title)->length() > 60 ? 'text-danger-500' : 'text-ink-muted' }}">{{ str($meta_title)->length() }}/60</span></label>
                    <label class="block text-sm"><span class="font-semibold text-ink-soft">Meta description</span>
                        <textarea wire:model.live.debounce.300ms="meta_description" rows="3" class="mt-1 w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm text-ink"></textarea>
                        <span class="mt-1 block text-xs {{ str($meta_description)->length() > 160 ? 'text-danger-500' : 'text-ink-muted' }}">{{ str($meta_description)->length() }}/160</span></label>
                </div>
            </div>

            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <h2 class="font-display text-lg">FAQ (renders FAQPage schema)</h2>
                <div class="mt-3 flex flex-col gap-3">
                    @foreach ($faq as $i => $item)
                        <div class="rounded-lg border border-line bg-paper p-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-ink-muted">Q{{ $i + 1 }}</span>
                                <button type="button" wire:click="removeFaqItem({{ $i }})" class="text-xs text-ink-muted hover:text-ink">remove</button>
                            </div>
                            <input type="text" wire:model.live.debounce.300ms="faq.{{ $i }}.q" placeholder="Question"
                                   class="mt-2 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                            <textarea wire:model.live.debounce.300ms="faq.{{ $i }}.a" rows="2" placeholder="Answer (answer-first style)"
                                      class="mt-2 w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm text-ink"></textarea>
                        </div>
                    @endforeach
                    <button type="button" wire:click="addFaqItem" class="inline-flex min-h-[36px] items-center rounded-lg border border-dashed border-line px-3 text-xs font-semibold text-ink-soft">+ Add question</button>
                </div>
            </div>

            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <h2 class="font-display text-lg">Related services</h2>
                <p class="mt-1 text-xs text-ink-muted">Shown as "You may also need" on the leaf page.</p>
                <div class="mt-3 flex max-h-56 flex-col gap-1 overflow-y-auto">
                    @foreach ($relatedOptions as $option)
                        <label class="flex min-h-[36px] items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="related_ids" value="{{ $option->getKey() }}" class="h-4 w-4 rounded border-line">
                            <span class="text-ink-soft">{{ $option->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <label class="flex min-h-[44px] items-center gap-2 text-sm">
                    <input type="checkbox" wire:model.live="noindex" class="h-4 w-4 rounded border-line">
                    <span class="font-semibold text-ink">noindex this service</span>
                </label>
                @if ($noindex)
                    <textarea wire:model="noindex_reason" rows="2" placeholder="Reason (required, logged)" class="mt-2 w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm text-ink"></textarea>
                    @if (! $noindex_confirmed)
                        <button type="button" wire:click="$set('noindex_confirmed', true)" class="mt-2 inline-flex min-h-[36px] items-center rounded-lg border border-danger-500/40 bg-danger-500/10 px-3 text-xs font-semibold text-ink">
                            Confirm noindex
                        </button>
                    @endif
                @endif
            </div>
        </aside>
    </div>
</div>
