@extends ('emails.layouts.master')

@section ('content')

    <p>Hi there,</p>
    <p>Welcome to Spectero!</p>

    <p>Please verify your Spectero account by clicking the button below</p>
    <a target="_blank" href="{{ $verifyUrl }}">Verify Account</a>

@endsection