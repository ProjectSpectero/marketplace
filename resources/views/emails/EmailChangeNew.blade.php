@extends ('emails.layouts.master')

@section ('content')
    <h2>Verify Email</h2>

    <p>Hi {{ $salutation }},</p>
    <p>You have successfully changed your email address for your Spectero account.</p>

    <p>Please verify your new email by clicking the link below:</p>
    <a class="btn" target="_blank" href="{{ $verifyUrl }}">Verify Email</a>
@endsection