<div class="admin-screen">
@section('title', 'Posts — Sewa Admin')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">Posts</h1>
            <p class="eyebrow mt-1 text-ink-muted">Editorial · Blog & News</p>
        </div>
        <button type="button" wire:click="$set('showForm', true)"
                class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">
            New post
        </button>
    </div>

    {{-- Draft creation: every post MUST be born with a human author
         (authorship invariant, 07-blog-news §2). --}}
    @if ($showForm)
        <form wire:submit="create" class="mt-4 grid gap-3 rounded-xl border border-line bg-paper-2 p-4 sm:grid-cols-[2fr_1fr_1fr_auto]">
            <div>
                <label for="post-title" class="mb-1 block text-xs font-semibold text-ink-soft">Working title</label>
                <input id="post-title" type="text" wire:model="title" required maxlength="190"
                       class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand">
                @error('title') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="post-type" class="mb-1 block text-xs font-semibold text-ink-soft">Type</label>
                <select id="post-type" wire:model="postType"
                        class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                    <option value="blog">Blog</option>
                    <option value="news">News</option>
                </select>
            </div>
            <div>
                <label for="post-author" class="mb-1 block text-xs font-semibold text-ink-soft">Human author</label>
                <select id="post-author" wire:model="authorId" required
                        class="min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                    <option value="">Choose an author…</option>
                    @foreach ($authors as $author)
                        <option value="{{ $author->getKey() }}">{{ $author->name }}</option>
                    @endforeach
                </select>
                @error('authorId') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-end">
                <button type="submit"
                        class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">
                    Create draft
                </button>
            </div>
        </form>
    @endif

    <div class="mt-4 flex flex-wrap items-center gap-3">
        <input type="search" wire:model.live.debounce.300ms="q" placeholder="Search titles…"
               class="min-h-[44px] w-full max-w-xs rounded-lg border border-line bg-paper px-3 text-sm text-ink outline-none focus:border-brand sm:w-64"
               aria-label="Search posts">
        <select wire:model.live="status" aria-label="Filter by status"
                class="min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm text-ink">
            <option value="">All statuses</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="type" aria-label="Filter by type"
                class="min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm text-ink">
            <option value="">All types</option>
            @foreach ($types as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <button type="button" wire:click="$toggle('needsReview')" aria-pressed="{{ $needsReview ? 'true' : 'false' }}"
                class="inline-flex min-h-[44px] items-center gap-2 rounded-full border px-4 text-sm font-semibold {{ $needsReview ? 'border-brand bg-brand/10 text-ink' : 'border-line text-ink-soft hover:bg-paper-3' }}">
            Needs review
            <span class="inline-flex min-w-[20px] items-center justify-center rounded-full bg-warning-500/20 px-1.5 text-xs font-bold text-ink">{{ $reviewCount }}</span>
        </button>
    </div>

    @error('workflow') <p class="mt-3 rounded-lg bg-danger-500/10 px-4 py-3 text-sm font-medium text-ink">{{ $message }}</p> @enderror

    <div class="mt-4 overflow-x-auto rounded-xl border border-line bg-paper-2">
        <table class="w-full min-w-[760px] text-start text-sm">
            <thead>
                <tr class="border-b border-line text-ink-muted">
                    <th class="px-4 py-3 text-start font-semibold">Title</th>
                    <th class="px-4 py-3 text-start font-semibold">Author / path</th>
                    <th class="px-4 py-3 text-start font-semibold">Status</th>
                    <th class="px-4 py-3 text-start font-semibold">Updated</th>
                    <th class="px-4 py-3 text-end font-semibold"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($posts as $post)
                    <tr class="border-b border-line/60">
                        <td class="px-4 py-3">
                            <span class="font-medium text-ink">{{ $post->title }}</span>
                            <span class="mt-0.5 block text-xs text-ink-muted">{{ ucfirst($post->type->value) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-medium text-ink-soft">{{ $post->author?->name ?? '—' }}</span>
                            <code class="mt-0.5 block text-xs text-ink-muted">{{ $post->publicPath() }}</code>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $tones = ['published' => 'bg-success-500/15 text-ink', 'draft' => 'bg-paper-3 text-ink-soft', 'review' => 'bg-warning-500/15 text-ink', 'scheduled' => 'bg-accent/20 text-ink'];
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $tones[$post->status->value] ?? $tones['draft'] }}">
                                {{ $post->status->label() }}
                            </span>
                            @if ($post->status->value === 'scheduled' && $post->scheduled_at)
                                <span class="mt-1 block text-xs text-ink-muted">{{ $post->scheduled_at->format('d M H:i') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-ink-soft">{{ $post->updated_at?->diffForHumans() }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                @if ($canReview && $post->status->value === 'review')
                                    <button wire:click="approve('{{ $post->getKey() }}')" type="button"
                                            class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">Approve</button>
                                @endif
                                @if (in_array($post->status->value, ['draft', 'review', 'scheduled'], true))
                                    <button wire:click="submitForReview('{{ $post->getKey() }}')" type="button"
                                            class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">Submit</button>
                                @endif
                                @if ($canReview && in_array($post->status->value, ['draft', 'review', 'scheduled'], true))
                                    <button wire:click="publish('{{ $post->getKey() }}')" type="button"
                                            class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">Publish</button>
                                @endif
                                @if ($post->status->value === 'published')
                                    <a href="{{ $post->publicPath() }}" target="_blank" rel="noopener"
                                       class="inline-flex min-h-[36px] items-center rounded-lg border border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">View</a>
                                @endif
                                <a href="{{ route('admin.posts.edit', ['post' => $post->getKey()]) }}"
                                   class="inline-flex min-h-[36px] items-center rounded-lg bg-brand px-3 text-xs font-semibold text-brand-ink hover:opacity-90">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10">
                            <div class="flex flex-col items-center gap-2 text-center">
                                <p class="font-display text-lg text-ink">No posts match</p>
                                <p class="text-sm text-ink-soft">Adjust the filters, or create the first draft.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $posts->links() }}</div>
</div>
