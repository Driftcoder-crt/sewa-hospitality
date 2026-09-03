{{-- newsletter.issue — campaign body (markdown-rendered HTML supplied
     by the Newsletter manager) + mandatory one-click unsubscribe. --}}
@extends('mail.shared.layout')

@section('body')
    <div style="font-size:15px; line-height:1.65;">
        {!! $issueHtml !!}
    </div>
@endsection

@section('footer_note')
    You are subscribed to Sewa Hospitality updates.
    <a href="{{ $unsubscribeUrl }}" style="color:#0E7C66;">Unsubscribe</a> (one click, no questions).
@endsection
