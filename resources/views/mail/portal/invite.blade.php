{{-- portal.invite (10-email §4): magic onboarding link + role summary. --}}
@extends('mail.shared.layout')

@section('body')
    <p style="margin:0 0 14px; font-size:17px; font-weight:600;">Hello {{ $name }},</p>

    <p style="margin:0 0 14px;">{{ $organization->name }} has set you up on the
    <strong>Sewa Hospitality client portal</strong> — one place for your move timeline,
    documents, direct chat with your consultant team, and (for managers) invoices.</p>

    <p style="margin:0 0 4px;"><strong>Your access:</strong> {{ $roleLabel }}</p>
    <p style="margin:0 0 14px;"><strong>Organization:</strong> {{ $organization->name }}</p>

    <p style="margin:0 0 14px;">The secure button below sets your password and signs you in —
    it works once and expires in 72 hours. After that, sign in at
    <a href="{{ url('/login') }}" style="color:#0E7C66;">the portal login</a> with your email
    (the address this email reached) and your password.</p>
@endsection

@section('cta')
    <a href="{{ $acceptUrl }}" style="display:inline-block; background-color:#0E7C66; color:#ffffff; text-decoration:none; font-weight:600; font-size:14px; padding:12px 28px; border-radius:999px;">Set up your access</a>
@endsection

@section('footer_note')
    This invitation was sent at the request of {{ $organization->name }}. The link expires in 72 hours.
@endsection
