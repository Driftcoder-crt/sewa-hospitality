{{-- cms::page — the CMS render path (04-modules/01-cms.md §3):
     page → blocks → each block through its Blade component with the
     media pipeline and the SEO meta service. Rendered HTML is cached
     full-page by PageRenderer::cachedHtml() keyed path+locale. --}}
@extends('layouts.app')

@section('title', $meta->title)
@section('meta_description', $meta->description)

@push('head')
    {!! $metaTags !!}
@endpush

@section('content')
    @include('cms.partials.blocks', ['blocks' => $blocks, 'leadIndex' => $leadIndex])
@endsection
