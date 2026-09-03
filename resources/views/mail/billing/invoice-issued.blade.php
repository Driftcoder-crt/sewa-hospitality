{{-- invoice.issued (10-email §4): number, amount, due date, PDF
     attached note + portal CTA. --}}
@extends('mail.shared.layout')

@section('body')
    <p style="margin:0 0 14px; font-size:17px; font-weight:600;">Dear {{ $invoice->organization?->name ?? 'client' }},</p>

    <p style="margin:0 0 14px;">Please find attached invoice <strong>{{ $invoice->number }}</strong>
    for {{ \App\Modules\Billing\Services\TaxCalculator::money($invoice->total) }}.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; margin:0 0 14px;">
        <tr>
            <td style="padding:8px 0; border-bottom:1px solid #E8E1D5; font-size:14px;">Invoice number</td>
            <td align="right" style="padding:8px 0; border-bottom:1px solid #E8E1D5; font-size:14px; font-weight:600;">{{ $invoice->number }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0; border-bottom:1px solid #E8E1D5; font-size:14px;">Amount due</td>
            <td align="right" style="padding:8px 0; border-bottom:1px solid #E8E1D5; font-size:14px; font-weight:600;">{{ \App\Modules\Billing\Services\TaxCalculator::money($invoice->amountDue()) }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0; font-size:14px;">Due by</td>
            <td align="right" style="padding:8px 0; font-size:14px; font-weight:600;">
                @if ($invoice->due_at)
                    {{ $invoice->due_at->format('d M Y') }}
                @else
                    On receipt
                @endif
            </td>
        </tr>
    </table>

    <p style="margin:0; font-size:13px; color:#8A7F6E;">The PDF copy is attached. You can also download it
    anytime from your client portal. Payment details are on the invoice — write to
    <a href="mailto:{{ config('sewa.emails.billing') }}" style="color:#0E7C66;">{{ config('sewa.emails.billing') }}</a>
    with any questions.</p>
@endsection

@section('cta')
    <a href="{{ $portalUrl }}" style="display:inline-block; background-color:#0E7C66; color:#ffffff; text-decoration:none; font-weight:600; font-size:14px; padding:12px 28px; border-radius:999px;">Open your portal</a>
@endsection

@section('footer_note')
    Sent because Sewa Hospitality issued an invoice to your organization.
@endsection
