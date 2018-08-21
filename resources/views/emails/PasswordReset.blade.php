@extends ('emails.layouts.master')

@section ('content')
    <p>Hi,</p>
    <p>Someone requested a new password for your Spectero account. If this was you, click the link below to proceed.</p>

    <a class="btn" target="_blank" href="{{ $resetUrl }}">Reset Password</a>

    <p>Please note that this one-time-use link is only usable from the same computer (or device) you performed the reset request from. Any older reset-link(s) have also been invalidated.</p>
    <p>If you didn't make this request then you can safely ignore this email. The link will expire in {{ $expires->diffInMinutes(\Carbon\Carbon::now()) }} minutes.</p>

    <p>This request was made from the IP address <b>{{ $requesterIP }}</b>.</p>
@endsection