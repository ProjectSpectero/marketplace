@extends ('emails.layouts.master')

@section ('content')

    <p>Hi there,</p>
    <p>You have successfully changed your email address</p>

    <p>Please verify your new email by clicking the button below</p>
    <a target="_blank" href="{{ $verifyUrl }}">Verify Account</a>

@endsection