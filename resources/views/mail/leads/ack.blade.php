{{-- lead.ack — warm acknowledgment to the lead (10-email §4): what
     happens next + the published SLA promise + reply-to. Locale-aware
     strings ride the translations table via LeadAckMail (11-multilingual
     §5): reviewed translations render in the lead's language; EN copy
     is the honest default, with the reply-any-language note when the
     lead's language isn't served yet. --}}
@extends('mail.shared.layout')

@section('body')
    <p style="margin:0 0 14px; font-size:17px; font-weight:600;">{{ str_replace(':name', $lead->name, $strings['greeting']) }}</p>

    <p style="margin:0 0 14px;">{{ str_replace(':kind', mb_strtolower($lead->type->label()), $strings['intro']) }}</p>

    <p style="margin:0 0 14px;"><strong>{{ $strings['next'] }}</strong></p>
    <p style="margin:0 0 4px;">1. {{ $strings['step_1'] }}</p>
    <p style="margin:0 0 4px;">2. {{ str_replace(':window', $lead->type === \App\Modules\Leads\Enums\LeadType::QuoteRequest ? '4 business hours' : '2 business hours', $strings['step_2']) }}</p>
    <p style="margin:0 0 14px;">3. {{ $strings['step_3'] }}</p>

    <p style="margin:0;">{{ str_replace(':phone', '+91 98732 55531', $strings['urgent']) }}</p>

    @if ($reply_note)
        <p style="margin:14px 0 0; font-style:italic;">{{ $reply_note }}</p>
    @endif
@endsection

@section('footer_note')
    You received this email because you submitted a form on sewahospitality.com.
@endsection
