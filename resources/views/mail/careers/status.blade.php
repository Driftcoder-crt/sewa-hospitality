{{-- application.status — candidate stage update (06-hr §5). Honest
     what's-next copy per stage; rejections are respectful and final. --}}
@extends('mail.shared.layout')

@section('body')
    <p style="margin:0 0 14px; font-size:17px; font-weight:600;">Dear {{ $application->applicant_name }},</p>

    <p style="margin:0 0 14px;">An update on your application
    @if ($application->posting)
        for <strong>{{ $application->posting->title }}</strong>:
    @else
        :
    @endif
    your application has moved to <strong>{{ $stageLabel }}</strong>.</p>

    @php($next = [
        'Screening' => 'Our recruiters are reviewing your resume against the role.',
        'Shortlisted' => 'You are on the shortlist — the hiring team takes it from here.',
        'Interview' => 'Our team will reach out shortly to schedule your interview.',
        'Offer' => 'Congratulations — an offer is being prepared. Watch your inbox.',
        'Rejected' => 'We will not be moving forward this time. We keep every application on file
        for future roles that fit better — and we wish you well meanwhile.',
    ][$stageLabel] ?? 'Our team will keep you posted.')

    <p style="margin:0 0 14px;">{{ $next }}</p>

    <p style="margin:0;">Questions? Reply to this email or call
    <a href="tel:+919873255531" style="color:#0E7C66;">+91 98732 55531</a>.</p>
@endsection

@section('footer_note')
    Sent because you applied via sewahospitality.com/careers.
@endsection
