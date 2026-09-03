<div class="admin-screen">
@section('title', 'Edit post — Sewa Admin')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">Edit post</h1>
            <p class="eyebrow mt-1 text-ink-muted">Editorial · {{ ucfirst($post->type->value) }} · {{ $post->status->label() }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.posts') }}"
               class="inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm font-semibold text-ink-soft hover:bg-paper-3">Back</a>
            <button type="button" wire:click="save"
                    class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">
                Save
            </button>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[2fr_1fr]">
        {{-- Main column --}}
        <div class="flex flex-col gap-4">
            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <label for="pe-title" class="mb-1 block text-xs font-semibold text-ink-soft">Title</label>
                <input id="pe-title" type="text" wire:model="title" required maxlength="190"
                       class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand">
                @error('title') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror

                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="pe-slug" class="mb-1 block text-xs font-semibold text-ink-soft">Slug</label>
                        <input id="pe-slug" type="text" wire:model="slug" required
                               pattern="[a-z0-9-]+" class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand">
                        @error('slug') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="pe-type" class="mb-1 block text-xs font-semibold text-ink-soft">Type</label>
                        <select id="pe-type" wire:model="type" class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                            <option value="blog">Blog</option>
                            <option value="news">News</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <div class="mb-1 flex items-center justify-between">
                    <label for="pe-body" class="block text-xs font-semibold text-ink-soft">Body (sanitised HTML — single H2 ladder, no inline H1)</label>
                    <span class="text-xs text-ink-muted">{{ $wordCount }} words · ~{{ $readingTime }} min read</span>
                </div>
                <textarea id="pe-body" wire:model="body" rows="18" required
                          class="w-full rounded-lg border border-line bg-paper px-3 py-2 font-mono text-sm text-ink outline-none focus:border-brand"></textarea>
                @error('body') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <label for="pe-excerpt" class="mb-1 flex items-center justify-between text-xs font-semibold text-ink-soft">
                    <span>Excerpt (≥ 40 chars — publish gate)</span>
                    <span class="{{ mb_strlen($excerpt) < 40 ? 'text-warning-600' : 'text-ink-muted' }}">{{ mb_strlen($excerpt) }}/500</span>
                </label>
                <textarea id="pe-excerpt" wire:model="excerpt" rows="3" required maxlength="500"
                          class="w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm text-ink outline-none focus:border-brand"></textarea>
                @error('excerpt') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
            </div>

            {{-- Review notes thread (four-eyes workflow) --}}
            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <h2 class="font-display text-sm font-semibold text-ink">Review notes</h2>
                @if (trim((string) $post->review_notes) !== '')
                    <pre class="mt-2 max-h-48 overflow-y-auto whitespace-pre-wrap rounded-lg bg-paper-3 p-3 text-xs leading-relaxed text-ink-soft">{{ $post->review_notes }}</pre>
                @else
                    <p class="mt-2 text-xs text-ink-muted">No notes yet.</p>
                @endif
                <form wire:submit="addReviewNote" class="mt-3 flex flex-col gap-2 sm:flex-row">
                    <input type="text" wire:model="reviewNote" placeholder="Add a note for the reviewer…"
                           class="min-h-[44px] flex-1 rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand">
                    <button type="submit"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-line px-4 text-sm font-semibold text-ink-soft hover:bg-paper-3">Add note</button>
                </form>
                @error('reviewNote') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Side column --}}
        <div class="flex flex-col gap-4">
            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <h2 class="font-display text-sm font-semibold text-ink">Publication</h2>
                <div class="mt-3 flex flex-col gap-3">
                    <div>
                        <label for="pe-author" class="mb-1 block text-xs font-semibold text-ink-soft">Human author (required)</label>
                        <select id="pe-author" wire:model="authorId" required
                                class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                            <option value="">Choose an author…</option>
                            @foreach ($authors as $author)
                                <option value="{{ $author->getKey() }}">{{ $author->name }}</option>
                            @endforeach
                        </select>
                        @error('authorId') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="pe-scheduled" class="mb-1 block text-xs font-semibold text-ink-soft">Schedule for (blank = publish now)</label>
                        <input id="pe-scheduled" type="datetime-local" wire:model="scheduledAt"
                               class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand">
                        @error('scheduledAt') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <h2 class="font-display text-sm font-semibold text-ink">Taxonomy</h2>
                <fieldset class="mt-3 max-h-56 overflow-y-auto">
                    <legend class="sr-only">Categories</legend>
                    @forelse ($categories as $category)
                        <label class="flex min-h-[36px] items-center gap-2 text-sm text-ink-soft {{ $category->parent_id ? 'ps-5' : 'font-medium text-ink' }}">
                            <input type="checkbox" wire:model="categoryIds" value="{{ $category->getKey() }}"
                                   class="size-4 rounded border-line text-brand focus:ring-brand">
                            {{ $category->name }}
                        </label>
                    @empty
                        <p class="text-xs text-ink-muted">No categories yet — seed them first.</p>
                    @endforelse
                </fieldset>
                @error('categoryIds.*') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror

                <label for="pe-tags" class="mb-1 mt-3 block text-xs font-semibold text-ink-soft">Tags (comma-separated)</label>
                <input id="pe-tags" type="text" wire:model="tagsCsv" placeholder="gurugram, leases, expat"
                       class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand">
            </div>

            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <h2 class="font-display text-sm font-semibold text-ink">Cover media</h2>
                <label for="pe-cover" class="mb-1 mt-3 block text-xs font-semibold text-ink-soft">Media ID (uploads land via Media library)</label>
                <input id="pe-cover" type="text" wire:model="coverMediaId" placeholder="ULID or blank"
                       class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 font-mono text-xs text-ink outline-none focus:border-brand">
            </div>

            {{-- SEO drawer with live publish-gate report --}}
            <div class="rounded-xl border border-line bg-paper-2 p-4">
                <h2 class="font-display text-sm font-semibold text-ink">SEO</h2>
                <div class="mt-3 flex flex-col gap-3">
                    <div>
                        <label for="pe-meta-title" class="mb-1 block text-xs font-semibold text-ink-soft">Meta title (≤ 60)</label>
                        <input id="pe-meta-title" type="text" wire:model="metaTitle" maxlength="190"
                               class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand">
                        <p class="mt-1 text-xs {{ mb_strlen($metaTitle) > 60 ? 'text-warning-600' : 'text-ink-muted' }}">{{ mb_strlen($metaTitle) }}/60</p>
                    </div>
                    <div>
                        <label for="pe-meta-desc" class="mb-1 block text-xs font-semibold text-ink-soft">Meta description (≤ 160)</label>
                        <textarea id="pe-meta-desc" wire:model="metaDescription" rows="2" maxlength="320"
                                  class="w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm text-ink outline-none focus:border-brand"></textarea>
                        <p class="mt-1 text-xs {{ mb_strlen($metaDescription) > 160 ? 'text-warning-600' : 'text-ink-muted' }}">{{ mb_strlen($metaDescription) }}/160</p>
                    </div>
                    <div>
                        <label for="pe-canonical" class="mb-1 block text-xs font-semibold text-ink-soft">Canonical URL (optional)</label>
                        <input id="pe-canonical" type="url" wire:model="canonical" placeholder="https://"
                               class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand">
                    </div>
                    <label class="flex min-h-[36px] items-center gap-2 text-sm text-ink-soft">
                        <input type="checkbox" wire:model="noindex" class="size-4 rounded border-line text-brand focus:ring-brand">
                        noindex (requires explicit confirmation to publish)
                    </label>
                </div>

                <div class="mt-4 rounded-lg bg-paper-3 p-3">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-ink-soft">Publish gate</h3>
                    @forelse ($this->gateReport as $field => $problem)
                        <p class="mt-1 text-xs text-ink-soft">• <span class="font-mono">{{ $field }}</span>: {{ $problem }}</p>
                    @empty
                        <p class="mt-1 text-xs text-ink">Gate is clear — publishable.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
