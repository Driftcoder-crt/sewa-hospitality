{{-- Shared block render loop — used by CMS pages, service pages and
     city pages (single source; unknown types skipped, lead renders H1). --}}
@foreach ($blocks as $index => $block)
    @php
        $type = is_array($block) ? ($block['type'] ?? '') : '';
        $data = is_array($block) ? ($block['data'] ?? []) : [];
        $isLead = $index === $leadIndex;
    @endphp

    @continue(! \App\Modules\Cms\Services\BlockRegistry::has($type))

    <x-dynamic-component
        :component="\App\Modules\Cms\Services\BlockRegistry::component($type)"
        :data="$data"
        :is-lead="$isLead"
    />
@endforeach
