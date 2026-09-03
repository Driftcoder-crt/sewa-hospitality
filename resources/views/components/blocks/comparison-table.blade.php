{{-- B8 · Comparison Table (section-library §3) — 2–4 columns, row
     labels, highlight column, check/dash aware. Serviced-apartments-
     vs-hotels / tier comparisons. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $columns = is_array($data['columns'] ?? null) ? array_values($data['columns']) : [];
    $rows = is_array($data['rows'] ?? null) ? array_values($data['rows']) : [];
    $highlight = max(1, (int) ($data['highlight'] ?? 0));
@endphp

<div {{ $attributes }}>
    <div data-theme="light" class="px-4 py-10 md:px-6 md:py-12">
        <div class="container mx-auto max-w-4xl">
            @if ($data['heading'] ?? null)
                <h2 class="font-display text-2xl md:text-3xl">{{ $data['heading'] }}</h2>
            @endif

            <div class="mt-5 overflow-x-auto rounded-2xl border border-line">
                <table class="w-full min-w-[560px] text-sm">
                    <thead>
                        <tr class="border-b border-line bg-paper-2">
                            <th class="px-4 py-3 text-start font-semibold text-ink-soft" scope="col"><span class="sr-only">Feature</span></th>
                            @foreach ($columns as $ci => $column)
                                <th scope="col"
                                    class="px-4 py-3 text-start font-semibold {{ ($ci + 1) === $highlight ? 'bg-brand/10 text-ink' : 'text-ink' }}">
                                    {{ $column['label'] ?? '' }}
                                    @if (($ci + 1) === $highlight) <span class="ms-1 rounded-full bg-brand px-2 py-0.5 text-xs text-brand-ink">recommended</span> @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php($values = array_map('trim', explode(',', (string) ($row['values'] ?? ''))))
                            <tr class="border-b border-line/60 last:border-0">
                                <th scope="row" class="px-4 py-3 text-start font-medium text-ink">{{ $row['label'] ?? '' }}</th>
                                @foreach ($columns as $ci => $column)
                                    <td class="px-4 py-3 {{ ($ci + 1) === $highlight ? 'bg-brand/5 text-ink' : 'text-ink-soft' }}">
                                        @php($value = $values[$ci] ?? '')
                                        @if (in_array(mb_strtolower($value), ['yes', 'y', '✓', 'true'], true))
                                            <span class="inline-flex items-center gap-1 font-semibold text-ink"><span class="text-brand" aria-hidden="true">✓</span> Yes</span>
                                        @elseif (in_array(mb_strtolower($value), ['no', 'n', '—', '-', 'false'], true))
                                            <span class="inline-flex items-center gap-1 text-ink-muted"><span aria-hidden="true">—</span> No</span>
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
