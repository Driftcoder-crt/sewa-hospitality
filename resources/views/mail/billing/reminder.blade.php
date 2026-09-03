{{-- invoice.reminder (10-email §4): gentle tone, cap 3 (12 doc §5). --}}
@extends('mail.shared.layout')

@section('body')
    <p style="margin:0 0 14px; font-size:17px; font-weight:600;">Dear {{ $invoice->organization?->name ?? 'client' }},</p>

    @if ($daysPast <= 5)
        <p style="margin:0 0 14px;">A gentle nudge that invoice <strong>{{ $invoice->number }}</strong>
        ({{ \App\Modules\Billing\Services\TaxCalculator::money($invoice->amountDue()) }}) is now
        {{ $daysPast }} day{{ $daysPast === 1 ? '' : 's' }} past its due date.</p>
    @else
        <p style="margin:0 0 14px;">Invoice <strong>{{ $invoice->number }}</strong>
        ({{ \App\Modules\Billing\Services\TaxCalculator::money($invoice->amountDue()) }}) remains
        open — it is now {{ $daysPast }} days past its due date. If the invoice hasn't reached the
        right desk, or you need a copy of payment instructions, just reply to this email.</p>
    @endif

    <p style="margin:0; font-size:13px; color:#8A7F6E;">If you've already sent the payment, thank you —
    please disregard this note and accept our apologies for crossing wires.</p>
@endsection

@section('cta')
    <a href="mailto:{{ config('sewa.emails.billing') }}" style="display:inline-block; background-color:#0E7C66; color:#ffffff; text-decoration:none; font-weight:600; font-size:14px; padding:12px 28px; border-radius:999px;">Contact billing</a>
@endsection

@section('footer_note')
    Sent because an invoice to your organization is past due. Maximum three reminders are ever sent.
@endsection
