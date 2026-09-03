{{-- E2 · Lead Form Section (section-library §6): form island +
     benefits beside it + privacy note. The E2 block is the one true
     way forms embed on CMS pages — every submission rides the full
     lead pipeline (Turnstile, idempotency, SLA, CRM). --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $formType = in_array($data['form_type'] ?? 'contact', ['contact', 'quote', 'callback'], true) ? $data['form_type'] : 'contact';
    $benefits = is_array($data['benefits'] ?? null) ? array_values($data['benefits']) : [];
    $serviceId = isset($data['service_id']) && is_string($data['service_id']) && $data['service_id'] !== '' ? $data['service_id'] : null;
@endphp

<section {{ $attributes }} data-theme="light">
    <div class="px-4 py-14 md:px-6 md:py-20">
        <div class="container mx-auto grid gap-8 lg:grid-cols-2 lg:items-start">
            <div>
                @if (($data['heading'] ?? '') !== '')
                    @if ($isLead)
                        <h1 id="page-h1" class="font-display text-4xl md:text-5xl">{{ $data['heading'] }}</h1>
                    @else
                        <h2 class="font-display text-3xl md:text-4xl">{{ $data['heading'] }}</h2>
                    @endif
                @endif

                @if (($data['intro'] ?? '') !== '')
                    <p class="mt-4 max-w-xl text-lg text-ink-soft">{{ $data['intro'] }}</p>
                @endif

                @if ($benefits !== [])
                    <ul class="mt-8 flex flex-col gap-3">
                        @foreach ($benefits as $benefit)
                            @if (($benefit['text'] ?? '') !== '')
                                <li class="flex items-start gap-3">
                                    <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand/15 text-brand" aria-hidden="true">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                    </span>
                                    <span class="text-ink-soft">{{ $benefit['text'] }}</span>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif

                @if (($data['privacy_note'] ?? '') !== '')
                    <p class="mt-6 text-xs text-ink-muted">{{ $data['privacy_note'] }}</p>
                @endif
            </div>

            <div>
                @if ($formType === 'quote')
                    <livewire:leads.quote-form :context-service-id="$serviceId" />
                @elseif ($formType === 'callback')
                    <livewire:leads.callback-form />
                @else
                    <livewire:leads.contact-form :context-service-id="$serviceId" />
                @endif
            </div>
        </div>
    </div>
</section>
