{{-- D4 · Trust Checklist (section-library §5): Sewa-Verified style
     standards with check icons + link to the verified standard page. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $items = is_array($data['items'] ?? null) ? array_values($data['items']) : [];
    $linkVerified = ($data['link_verified'] ?? true) !== false;
@endphp

<section {{ $attributes }} data-theme="light">
    <div class="px-4 py-12 md:px-6 md:py-16">
        <div class="container mx-auto max-w-3xl">
            @if (($data['heading'] ?? '') !== '')
                <h2 class="font-display text-2xl md:text-3xl">{{ $data['heading'] }}</h2>
            @endif

            <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                @foreach ($items as $item)
                    @if (($item['text'] ?? '') !== '')
                        <li class="flex items-start gap-3 rounded-xl border border-line bg-paper-2 p-4">
                            <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand text-brand-ink" aria-hidden="true">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                            </span>
                            <span class="text-sm text-ink-soft">{{ $item['text'] }}</span>
                        </li>
                    @endif
                @endforeach
            </ul>

            @if ($linkVerified)
                <p class="mt-6 text-sm">
                    <a href="/housing/verified" class="font-semibold text-brand underline underline-offset-2 hover:no-underline">
                        Read the full Sewa Verified standard →
                    </a>
                </p>
            @endif
        </div>
    </div>
</section>
