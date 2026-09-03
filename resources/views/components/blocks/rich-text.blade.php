{{-- B1 · Rich Text (section-library §3) — sanitized wysiwyg with
     heading-ladder enforcement (HtmlSanitizer demotes h1→h2), pull-quote
     and callout styles. Single-source; the ONLY block emitting long-form
     HTML into the page. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $html = \App\Support\Cms\HtmlSanitizer::clean(is_array($data) ? ($data['html'] ?? '') : '');
@endphp

<div {{ $attributes }}>
    <div data-theme="light" class="px-4 py-12 md:px-6 md:py-14">
        <div class="container mx-auto max-w-3xl [&_h2]:font-display [&_h2]:mt-8 [&_h2]:text-2xl [&_h2]:md:text-3xl [&_h2]:first:mt-0 [&_h3]:font-display [&_h3]:mt-6 [&_h3]:text-xl [&_h4]:mt-5 [&_h4]:text-lg [&_h4]:font-semibold [&_p]:mt-3 [&_p]:leading-relaxed [&_p]:text-ink-soft [&_strong]:text-ink [&_ul]:mt-3 [&_ul]:list-disc [&_ul]:ps-6 [&_ol]:mt-3 [&_ol]:list-decimal [&_ol]:ps-6 [&_li]:mt-1 [&_li]:leading-relaxed [&_li]:text-ink-soft [&_a]:font-medium [&_a]:text-brand [&_a]:underline [&_a]:underline-offset-2 [&_blockquote]:my-5 [&_blockquote]:border-s-4 [&_blockquote]:border-accent [&_blockquote]:ps-4 [&_blockquote]:font-display [&_blockquote]:text-lg [&_blockquote]:text-ink [&_img]:mt-5 [&_img]:rounded-xl [&_figure]:mt-5 [&_figcaption]:mt-2 [&_figcaption]:text-xs [&_figcaption]:text-ink-muted [&_table]:mt-5 [&_table]:w-full [&_table]:text-sm [&_th]:border-b [&_th]:border-line [&_th]:py-2 [&_th]:text-start [&_td]:border-b [&_td]:border-line/60 [&_td]:py-2 [&_code]:rounded [&_code]:bg-paper-2 [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:text-sm [&_hr]:my-6 [&_hr]:border-line">
            {!! $html !!}
        </div>
    </div>
</div>
