@extends ('emails.layouts.master')

@section ('content')
    <p>Hi there,</p>
    <p>Welcome to Spectero! We're so glad to have you on our platform.</p>

    <p>You may now login and access the platform.</p>
    <a target="_blank" href="{{ $loginUrl }}">Login now!</a>
@endsection