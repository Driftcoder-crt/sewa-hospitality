@extends('layouts.portal')

@section('title', 'Documents — Move '.$move->reference.' — Sewa Hospitality Portal')

@section('content')
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-1">
            <p class="eyebrow text-ink-muted">{{ $move->reference }}</p>
            <h1 class="font-display text-3xl">Documents</h1>
        </div>

        {{-- Category filter --}}
        <nav aria-label="Filter by category" class="flex flex-wrap gap-2">
            <a href="{{ route('portal.documents', $move) }}"
               class="min-h-[44px] rounded-full px-4 py-2 text-sm font-medium {{ $activeCategory ? 'border border-line text-ink-soft' : 'bg-brand text-brand-ink' }}">
                All
            </a>
            @foreach (\App\Modules\Portal\Enums\DocumentCategory::options() as $value => $label)
                <a href="{{ route('portal.documents', ['move' => $move, 'category' => $value]) }}"
                   class="min-h-[44px] rounded-full px-4 py-2 text-sm font-medium {{ $activeCategory === $value ? 'bg-brand text-brand-ink' : 'border border-line text-ink-soft hover:bg-paper-3' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        @forelse ($grouped as $categoryValue => $documents)
            <section aria-label="{{ \App\Modules\Portal\Enums\DocumentCategory::from($categoryValue)->label() }}" class="rounded-xl border border-line bg-paper-2">
                <h2 class="border-b border-line p-4 font-display text-lg">
                    {{ \App\Modules\Portal\Enums\DocumentCategory::from($categoryValue)->label() }}
                    <span class="ml-1 text-sm font-normal text-ink-muted">({{ $documents->count() }})</span>
                </h2>
                <ul role="list">
                    @foreach ($documents as $document)
                        <li class="flex flex-col gap-2 border-b border-line p-4 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-medium">{{ $document->title }}</p>
                                <p class="mt-0.5 text-xs text-ink-muted">
                                    Uploaded {{ $document->created_at->format('d M Y') }} by {{ $document->uploader?->name ?? 'your consultant team' }}
                                    @if ($document->expires_at)
                                        @if ($document->isExpiringSoon())
                                            <span class="font-semibold text-danger"> · expires {{ $document->expires_at->format('d M Y') }}</span>
                                        @else
                                            · valid until {{ $document->expires_at->format('d M Y') }}
                                        @endif
                                    @endif
                                </p>
                            </div>
                            <a href="{{ $document->downloadUrl() }}"
                               class="inline-flex min-h-[44px] items-center justify-center rounded-full border border-brand px-5 text-sm font-semibold text-brand hover:bg-brand hover:text-brand-ink">
                                Download
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <p class="rounded-xl border border-dashed border-line bg-paper-2 p-6 text-sm text-ink-soft">
                No documents{{ $activeCategory ? ' in this category' : '' }} yet — your consultant team publishes them as the move progresses.
            </p>
        @endforelse

        <p class="text-xs text-ink-muted">Download links are secure and expire after 15 minutes — simply re-open this page for a fresh link. Every download is logged for your safety.</p>
    </div>
@endsection
