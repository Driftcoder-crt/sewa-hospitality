{{-- application.ack — candidate confirmation (10-email §4): what's next
     + the data-retention note (06-hr §5). --}}
@extends('mail.shared.layout')

@section('body')
    <p style="margin:0 0 14px; font-size:17px; font-weight:600;">Dear {{ $application->applicant_name }},</p>

    <p style="margin:0 0 14px;">Thank you for applying to Sewa Hospitality
    @if ($application->posting)
        for the <strong>{{ $application->posting->title }}</strong> role ({{ $application->posting->location_text }}).
    @else
        .
    @endif</p>

    <p style="margin:0 0 4px;"><strong>What happens next:</strong></p>
    <p style="margin:0 0 4px;">1. Our recruiters review your resume.</p>
    <p style="margin:0 0 4px;">2. If there's a match, we reach out for a screening conversation.</p>
    <p style="margin:0 0 14px;">3. You hear from us either way once the role's screening round closes.</p>

    <p style="margin:0; color:#8A7F6E; font-size:13px;">We keep your application data only as long as needed for this process,
    per our privacy policy. If you'd like it deleted earlier, write to
    <a href="mailto:{{ config('sewa.emails.careers') }}" style="color:#0E7C66;">{{ config('sewa.emails.careers') }}</a>.</p>
@endsection

@section('footer_note')
    Sent because you applied via sewahospitality.com/careers.
@endsection
