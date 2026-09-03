{{-- lead.received — ops + assigned consultant notification (10-email §4). --}}
@extends('mail.shared.layout')

@section('body')
    <p style="margin:0 0 14px; font-size:17px; font-weight:600;">New {{ $lead->type->label() }} — {{ $lead->source->label() }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; font-size:14px;">
        <tr><td style="padding:4px 0; color:#8A7F6E; width:110px;">Name</td><td style="padding:4px 0;">{{ $lead->name }}</td></tr>
        <tr><td style="padding:4px 0; color:#8A7F6E;">Email</td><td style="padding:4px 0;">{{ $lead->email }}</td></tr>
        @if ($lead->phone)<tr><td style="padding:4px 0; color:#8A7F6E;">Phone</td><td style="padding:4px 0;">{{ $lead->phone }}</td></tr>@endif
        @if ($lead->company)<tr><td style="padding:4px 0; color:#8A7F6E;">Company</td><td style="padding:4px 0;">{{ $lead->company }}</td></tr>@endif
        @if ($lead->service)<tr><td style="padding:4px 0; color:#8A7F6E;">Service</td><td style="padding:4px 0;">{{ $lead->service->name }}</td></tr>@endif
        <tr><td style="padding:4px 0; color:#8A7F6E;">Locale</td><td style="padding:4px 0;">{{ $lead->locale }}</td></tr>
        <tr><td style="padding:4px 0; color:#8A7F6E;">SLA due</td><td style="padding:4px 0;">{{ $lead->sla_due_at?->timezone('Asia/Kolkata')->format('d M Y H:i') ?? 'n/a' }} IST</td></tr>
    </table>

    @if ($lead->message)
        <p style="margin:16px 0 6px; font-weight:600;">Message</p>
        <p style="margin:0; background-color:#FAF7F2; border-left:3px solid #0E7C66; padding:10px 14px; white-space:pre-wrap;">{{ $lead->message }}</p>
    @endif
@endsection

@section('cta')
    <a href="{{ rtrim(config('app.url', 'https://sewahospitality.com'), '/') }}/admin/leads/{{ $lead->getKey() }}"
       style="display:inline-block; background-color:#0E7C66; color:#ffffff; font-weight:600; font-size:14px; padding:12px 24px; border-radius:999px; text-decoration:none;">
        Open in CRM
    </a>
@endsection

@section('footer_note')
    Internal notification — assigned: {{ $lead->assignedTo?->email ?? 'unassigned' }} · ops: {{ implode(', ', $opsEmails ?? []) }}
@endsection
