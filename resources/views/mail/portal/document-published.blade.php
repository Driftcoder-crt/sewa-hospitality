{{-- document.published (10-email §4): category + portal link — the
     document NEVER travels as an attachment (04 doc §5). --}}
@extends('mail.shared.layout')

@section('body')
    <p style="margin:0 0 14px; font-size:17px; font-weight:600;">Hello {{ $recipientName }},</p>

    <p style="margin:0 0 14px;">A new document is available in your portal:</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; margin:0 0 14px;">
        <tr>
            <td style="padding:8px 0; border-bottom:1px solid #E8E1D5; font-size:14px;">Document</td>
            <td align="right" style="padding:8px 0; border-bottom:1px solid #E8E1D5; font-size:14px; font-weight:600;">{{ $document->title }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0; border-bottom:1px solid #E8E1D5; font-size:14px;">Category</td>
            <td align="right" style="padding:8px 0; border-bottom:1px solid #E8E1D5; font-size:14px;">{{ $document->category?->label() }}</td>
        </tr>
        @if ($document->expires_at)
            <tr>
                <td style="padding:8px 0; font-size:14px;">Valid until</td>
                <td align="right" style="padding:8px 0; font-size:14px;">{{ $document->expires_at->format('d M Y') }}</td>
            </tr>
        @endif
    </table>

    <p style="margin:0; font-size:13px; color:#8A7F6E;">For your security we never send documents as
    email attachments — download from the portal, where every access is logged.</p>
@endsection

@section('cta')
    <a href="{{ $portalUrl }}" style="display:inline-block; background-color:#0E7C66; color:#ffffff; text-decoration:none; font-weight:600; font-size:14px; padding:12px 28px; border-radius:999px;">Open your documents</a>
@endsection

@section('footer_note')
    Sent because a document was published to your portal account.
@endsection
