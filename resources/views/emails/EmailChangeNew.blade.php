@extends ('emails.layouts.master')

@section ('content')
    <h2>Verify Email</h2>

    <p>Hi there,</p>
    <p>You have successfully changed your email address for your Spectero account.</p>

    <p>Please verify your new email by clicking the link below:</p>
    <a target="_blank" href="{{ $verifyUrl }}">Verify Email</a>
@endsection