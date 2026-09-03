{{-- newsletter.confirm — double opt-in (10-email §4): one CTA only. --}}
@extends('mail.shared.layout')

@section('body')
    <p style="margin:0 0 14px; font-size:17px; font-weight:600;">One click to confirm</p>

    <p style="margin:0 0 14px;">You asked to hear from Sewa Hospitality — relocation guides, city notes
    and housing updates, a few times a month. Confirm your address below and we'll take it from there.</p>

    <p style="margin:0; color:#8A7F6E; font-size:13px;">Didn't request this? Ignore this email — you won't be subscribed.</p>
@endsection

@section('cta')
    <a href="{{ $confirmUrl }}"
       style="display:inline-block; background-color:#0E7C66; color:#ffffff; font-weight:600; font-size:14px; padding:12px 28px; border-radius:999px; text-decoration:none;">
        Confirm subscription
    </a>
@endsection

@section('footer_note')
    You can unsubscribe at any time — every email carries a one-click link.
@endsection
