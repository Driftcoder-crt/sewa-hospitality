{{-- application.received — careers@ + recruiter notification with the
     24h SIGNED resume link (PII never leaves the private disk openly). --}}
@extends('mail.shared.layout')

@section('body')
    <p style="margin:0 0 14px; font-size:17px; font-weight:600;">New application — {{ $application->applicant_name }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; font-size:14px;">
        <tr><td style="padding:4px 0; color:#8A7F6E; width:110px;">Role</td><td style="padding:4px 0;">{{ $application->posting?->title ?? 'General application' }}</td></tr>
        <tr><td style="padding:4px 0; color:#8A7F6E;">Email</td><td style="padding:4px 0;">{{ $application->applicant_email }}</td></tr>
        @if ($application->applicant_phone)<tr><td style="padding:4px 0; color:#8A7F6E;">Phone</td><td style="padding:4px 0;">{{ $application->applicant_phone }}</td></tr>@endif
        <tr><td style="padding:4px 0; color:#8A7F6E;">Source</td><td style="padding:4px 0;">{{ $application->source }} @if($application->source_detail) · {{ $application->source_detail }} @endif</td></tr>
        <tr><td style="padding:4px 0; color:#8A7F6E;">Resume</td><td style="padding:4px 0;"><a href="{{ $resumeUrl }}" style="color:#0E7C66;">Signed link (24h)</a></td></tr>
    </table>

    @if ($application->cover_message)
        <p style="margin:16px 0 6px; font-weight:600;">Cover message</p>
        <p style="margin:0; background-color:#FAF7F2; border-left:3px solid #0E7C66; padding:10px 14px; white-space:pre-wrap;">{{ $application->cover_message }}</p>
    @endif
@endsection

@section('cta')
    <a href="{{ rtrim(config('app.url', 'https://sewahospitality.com'), '/') }}/admin/applications"
       style="display:inline-block; background-color:#0E7C66; color:#ffffff; font-weight:600; font-size:14px; padding:12px 24px; border-radius:999px; text-decoration:none;">
        Open ATS pipeline
    </a>
@endsection

@section('footer_note')
    Internal notification — recruiters + careers@.
@endsection
