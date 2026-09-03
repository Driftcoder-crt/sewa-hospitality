{{-- move.stage_changed (10-email §4): stage, what's next, portal link. --}}
@extends('mail.shared.layout')

@section('body')
    <p style="margin:0 0 14px; font-size:17px; font-weight:600;">Hello {{ $recipientName }},</p>

    <p style="margin:0 0 14px;">Your move <strong>{{ $move->reference }}</strong>
    @if ($move->destinationCity)
        to {{ $move->destinationCity->name }}
    @endif
    has moved from <strong>{{ $fromLabel }}</strong> to <strong>{{ $toLabel }}</strong>.</p>

    <p style="margin:0 0 14px;">{{ $whatsNext }}</p>

    <p style="margin:0; font-size:13px; color:#8A7F6E;">Your checklist, documents and consultant chat
    are always available in the portal.</p>
@endsection

@section('cta')
    <a href="{{ $portalUrl }}" style="display:inline-block; background-color:#0E7C66; color:#ffffff; text-decoration:none; font-weight:600; font-size:14px; padding:12px 28px; border-radius:999px;">View your move</a>
@endsection

@section('footer_note')
    You receive move updates because you are part of this relocation.
@endsection
