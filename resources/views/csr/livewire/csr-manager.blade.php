<div class="admin-screen">
@section('title', 'CSR — Sewa Admin')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">CSR</h1>
            <p class="eyebrow mt-1 text-ink-muted">Community · NGO partners & stories</p>
        </div>
        <button type="button" wire:click="$set('showForm', true)"
                class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">
            New {{ $tab === 'partners' ? 'partner' : 'story' }}
        </button>
    </div>

    <div class="mt-5 flex flex-wrap items-center gap-2" role="tablist" aria-label="CSR tabs">
        @foreach (['partners' => 'NGO partners', 'stories' => 'Stories'] as $key => $label)
            <button type="button" role="tab" wire:click="$set('tab', '{{ $key }}')"
                    aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                    class="inline-flex min-h-[44px] items-center rounded-full border px-4 text-sm font-semibold {{ $tab === $key ? 'border-brand bg-brand text-brand-ink' : 'border-line text-ink-soft hover:bg-paper-3' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($showForm && $tab === 'partners')
        <form wire:submit="{{ $editingId === '' ? 'createPartner' : 'updatePartner' }}"
              class="mt-4 grid gap-3 rounded-xl border border-line bg-paper-2 p-4 md:grid-cols-2">
            <h2 class="font-display text-sm font-semibold text-ink md:col-span-2">{{ $editingId === '' ? 'New partner' : 'Edit partner' }}</h2>
            <div>
                <label for="p-name" class="mb-1 block text-xs font-semibold text-ink-soft">Name</label>
                <input id="p-name" type="text" wire:model="pName" required maxlength="190"
                       class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand">
                @error('pName') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="p-slug" class="mb-1 block text-xs font-semibold text-ink-soft">Slug</label>
                <input id="p-slug" type="text" wire:model="pSlug" placeholder="auto from name"
                       class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand">
                @error('pSlug') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="p-website" class="mb-1 block text-xs font-semibold text-ink-soft">Official website</label>
                <input id="p-website" type="url" wire:model="pWebsite" placeholder="https://"
                       class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand">
                @error('pWebsite') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="p-since" class="mb-1 block text-xs font-semibold text-ink-soft">Partner since</label>
                    <input id="p-since" type="number" min="1990" max="{{ now()->year }}" wire:model="pSince"
                           class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand">
                </div>
                <div>
                    <label for="p-status" class="mb-1 block text-xs font-semibold text-ink-soft">Status</label>
                    <select id="p-status" wire:model="pStatus" class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                        <option value="active">Active partnership</option>
                        <option value="archived">Past association</option>
                    </select>
                </div>
            </div>
            <div class="md:col-span-2">
                <label for="p-description" class="mb-1 block text-xs font-semibold text-ink-soft">Description</label>
                <textarea id="p-description" wire:model="pDescription" rows="2"
                          class="w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm text-ink outline-none focus:border-brand"></textarea>
            </div>

            {{-- Claims ledger: claim, as-of and source stand together --}}
            <fieldset class="rounded-lg bg-paper-3 p-3 md:col-span-2">
                <legend class="px-1 text-xs font-bold uppercase tracking-wide text-ink-soft">Measurable claim (all three fields required together)</legend>
                <div class="grid gap-3 md:grid-cols-3">
                    <div>
                        <label for="p-claim" class="mb-1 block text-xs font-semibold text-ink-soft">Claim</label>
                        <input id="p-claim" type="text" wire:model="pClaim" placeholder="e.g. 1,200 school kits delivered"
                               class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand">
                    </div>
                    <div>
                        <label for="p-claim-asof" class="mb-1 block text-xs font-semibold text-ink-soft">As of</label>
                        <input id="p-claim-asof" type="text" wire:model="pClaimAsOf" placeholder="Mar 2026"
                               class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand">
                    </div>
                    <div>
                        <label for="p-claim-source" class="mb-1 block text-xs font-semibold text-ink-soft">Source</label>
                        <input id="p-claim-source" type="text" wire:model="pClaimSource" placeholder="Partner letter, receipt no…"
                               class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand">
                    </div>
                </div>
                @error('pClaim') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                @error('pClaimAsOf') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                @error('pClaimSource') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
            </fieldset>

            <div>
                <label for="p-focus" class="mb-1 block text-xs font-semibold text-ink-soft">Focus areas (comma-separated)</label>
                <input id="p-focus" type="text" wire:model="pFocus" placeholder="education, disaster relief"
                       class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand">
            </div>
            <div class="flex items-end justify-end gap-2">
                <button type="button" wire:click="$set('showForm', false)"
                        class="inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm font-semibold text-ink-soft hover:bg-paper-3">Cancel</button>
                <button type="submit"
                        class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">
                    {{ $editingId === '' ? 'Create partner' : 'Save partner' }}
                </button>
            </div>
        </form>
    @endif

    @if ($showForm && $tab === 'stories')
        <form wire:submit="{{ $editingId === '' ? 'createStory' : 'updateStory' }}"
              class="mt-4 grid gap-3 rounded-xl border border-line bg-paper-2 p-4 md:grid-cols-2">
            <h2 class="font-display text-sm font-semibold text-ink md:col-span-2">{{ $editingId === '' ? 'New story' : 'Edit story' }}</h2>
            <div>
                <label for="s-title" class="mb-1 block text-xs font-semibold text-ink-soft">Title</label>
                <input id="s-title" type="text" wire:model="sTitle" required maxlength="190"
                       class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand">
                @error('sTitle') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="s-slug" class="mb-1 block text-xs font-semibold text-ink-soft">Slug</label>
                <input id="s-slug" type="text" wire:model="sSlug" placeholder="auto from title"
                       class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand">
                @error('sSlug') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="s-partner" class="mb-1 block text-xs font-semibold text-ink-soft">NGO partner</label>
                <select id="s-partner" wire:model="sPartnerId" class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                    <option value="">— none —</option>
                    @foreach ($partners as $partner)
                        <option value="{{ $partner->getKey() }}">{{ $partner->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="s-author" class="mb-1 block text-xs font-semibold text-ink-soft">Human author</label>
                <select id="s-author" wire:model="sAuthorId" class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                    <option value="">Me (signed-in user)</option>
                    @foreach ($authors as $author)
                        <option value="{{ $author->getKey() }}">{{ $author->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label for="s-body" class="mb-1 block text-xs font-semibold text-ink-soft">Body (sanitised HTML)</label>
                <textarea id="s-body" wire:model="sBody" rows="10" required
                          class="w-full rounded-lg border border-line bg-paper px-3 py-2 font-mono text-sm text-ink outline-none focus:border-brand"></textarea>
                @error('sBody') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
            </div>
            <label class="flex min-h-[36px] items-center gap-2 text-sm text-ink-soft md:col-span-2">
                <input type="checkbox" wire:model="sCrossPost" class="size-4 rounded border-line text-brand focus:ring-brand">
                Also cross-post to the blog feed when published
            </label>
            <div class="flex items-end justify-end gap-2 md:col-span-2">
                <button type="button" wire:click="$set('showForm', false)"
                        class="inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm font-semibold text-ink-soft hover:bg-paper-3">Cancel</button>
                <button type="submit"
                        class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">
                    {{ $editingId === '' ? 'Create draft' : 'Save story' }}
                </button>
            </div>
        </form>
    @endif

    @if ($tab === 'partners')
        <div class="mt-4 overflow-x-auto rounded-xl border border-line bg-paper-2">
            <table class="w-full min-w-[760px] text-start text-sm">
                <thead>
                    <tr class="border-b border-line text-ink-muted">
                        <th class="px-4 py-3 text-start font-semibold">Partner</th>
                        <th class="px-4 py-3 text-start font-semibold">Status</th>
                        <th class="px-4 py-3 text-start font-semibold">Claim ledger</th>
                        <th class="px-4 py-3 text-start font-semibold">Stories</th>
                        <th class="px-4 py-3 text-end font-semibold"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($partners as $partner)
                        <tr class="border-b border-line/60">
                            <td class="px-4 py-3">
                                <span class="font-medium text-ink">{{ $partner->name }}</span>
                                @if ($partner->website)
                                    <a href="{{ $partner->website }}" target="_blank" rel="noopener" class="ms-1 text-xs text-ink-soft underline">site ↗</a>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $partner->status->value === 'active' ? 'bg-success-500/15 text-ink' : 'bg-paper-3 text-ink-muted' }}">
                                    {{ $partner->status->label() }}
                                </span>
                            </td>
                            <td class="max-w-xs px-4 py-3 text-xs text-ink-soft">
                                @if ($partner->claim)
                                    {{ $partner->claim }} <span class="text-ink-muted">· as of {{ $partner->claim_as_of }}</span>
                                @else
                                    <span class="text-ink-muted">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-ink-soft">{{ $partner->stories_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="editPartner('{{ $partner->getKey() }}')" type="button"
                                            class="inline-flex min-h-[36px] items-center rounded-lg bg-brand px-3 text-xs font-semibold text-brand-ink hover:opacity-90">Edit</button>
                                    <button wire:click="deletePartner('{{ $partner->getKey() }}')" type="button" wire:confirm="Delete this partner?"
                                            class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center">
                                <p class="font-display text-lg text-ink">No partners yet</p>
                                <p class="mt-1 text-sm text-ink-soft">Named partners with a claims ledger — no anonymous generosity claims.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="mt-4 grid gap-3">
            @forelse ($stories as $story)
                <article class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-paper-2 p-4">
                    <div class="min-w-0">
                        <p class="font-medium text-ink">{{ $story->title }}</p>
                        <p class="mt-0.5 text-xs text-ink-muted">
                            /csr/{{ $story->slug }}
                            @if ($story->partner) · {{ $story->partner->name }} @endif
                            @if ($story->cross_post_to_blog) · cross-posts to blog @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $story->status === 'published' ? 'bg-success-500/15 text-ink' : 'bg-paper-3 text-ink-soft' }}">
                            {{ ucfirst($story->status) }}
                        </span>
                        <button wire:click="editStory('{{ $story->getKey() }}')" type="button"
                                class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">Edit</button>
                        <button wire:click="toggleStoryPublish('{{ $story->getKey() }}')" type="button"
                                class="inline-flex min-h-[36px] items-center rounded-lg {{ $story->status === 'published' ? 'border border-line text-ink-soft' : 'bg-brand text-brand-ink' }} px-3 text-xs font-semibold hover:opacity-90">
                            {{ $story->status === 'published' ? 'Unpublish' : 'Publish' }}
                        </button>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-line bg-paper-2 px-4 py-10 text-center">
                    <p class="font-display text-lg text-ink">No stories yet</p>
                    <p class="mt-1 text-sm text-ink-soft">Partnership stories publish to /csr/{slug} with Article JSON-LD.</p>
                </div>
            @endforelse
        </div>
    @endif
</div>
