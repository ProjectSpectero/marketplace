@extends ('emails.layouts.master')

@section ('content')
    <p>Hi,</p>
    <p>Someone requested a new password for your Spectero account. If this was you, click the link below to proceed:</p>

    <a target="_blank" href="{{ $resetUrl }}">Reset Password</a>

    <p>If you didn't make this request then you can safely ignore this email. The link will expire at {{ $expires }}.</p>

    <p>This request was made from the IP address <b>{{ $requesterIP }}</b>.</p>
@endsection