{{-- review.request (08 doc §4.3): the single customer-facing review ask.
     One polite initial send + one gentle 7-day follow-up, then a hard
     stop — copy mirrors that promise. Inline styles per the email-client
     constraints in mail/shared/layout. --}}
@extends('mail.shared.layout')

@section('body')
    <p style="margin:0 0 14px; font-size:17px; font-weight:600;">
        {{ $request->recipient_name ? 'Hi '.$request->recipient_name.',' : 'Hello,' }}
    </p>

    @if ($followUp)
        <p style="margin:0 0 14px;">Just a gentle nudge — if you have a moment, a short review of your move with Sewa Hospitality helps the next family move with confidence.</p>
    @else
        <p style="margin:0 0 14px;">Thank you for trusting Sewa Hospitality with your move. We hope everything landed the way it should have.</p>
    @endif

    <p style="margin:0 0 14px;">If you are happy with how it went, a Google review takes about a minute and genuinely helps other families choose well.</p>

    <p style="margin:24px 0 14px;">
        <a href="{{ $reviewUrl }}"
           style="display:inline-block; padding:12px 22px; border-radius:8px; background:#0E7C66; color:#FAF7F2; font-weight:600; text-decoration:none;">
            Leave a Google review
        </a>
    </p>

    <p style="margin:0 0 14px;">And if anything fell short — reply to this email instead. We would rather fix it than hear about it on a review.</p>

    <p style="margin:0;">This is the only time we will ask{{ $followUp ? ' — no further reminders, promise.' : '.' }}</p>
@endsection

@section('footer_note')
    You received this email because we completed a relocation move for your organisation.
@endsection
