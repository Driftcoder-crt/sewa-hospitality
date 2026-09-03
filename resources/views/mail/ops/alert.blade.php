{{-- ops.alert — generic ops monitor email (SLA breach, escalation,
     queue alerts): subject + bullet lines + one deep-link. --}}
@extends('mail.shared.layout')

@section('body')
    <p style="margin:0 0 14px; font-size:17px; font-weight:600;">⚠ {{ $alertSubject }}</p>

    <ul style="margin:0 0 14px; padding-left:18px;">
        @foreach ($lines as $line)
            <li style="margin-bottom:6px;">{{ $line }}</li>
        @endforeach
    </ul>
@endsection

@section('footer_note')
    Ops monitor — configuration: config/sewa.php · schedule: routes/console.php.
@endsection
