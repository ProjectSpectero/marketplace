@extends ('emails.layouts.master')

@section ('content')
    <p>Hi,</p>
    <p>Welcome to Spectero! We're so glad to have you on our platform.</p>

    <p>Please verify your email by clicking the link below:</p>
    <a target="_blank" href="{{ $verifyUrl }}">Verify My Account</a>

    <p>Once you verify your email using the link above you'll be able to login and access the platform.</p>
@endsection