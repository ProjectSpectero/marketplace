@extends ('emails.layouts.master')

@section ('content')

    <p>Hi there,</p>
    <p>Someone (hopefully you) requested that their Spectero password be reset from the IP address {{ $requesterIP }}.</p>

    <p>If this was you, please click the link below to proceed. This link will expire at {{ $expires }}</p>
    <a target="_blank" href="{{ $resetUrl }}">Verify Account</a>
    <p>It is also worth noting that any older reset tokens have automatically been expired.</p>

    <p>If not, your account will not be altered. There are no further actions you need to take.</p>
    <p>However, if you feel your account is being targeted by people attempting to take it over, it is usually a good idea to let us know via opening a support ticket.</p>

@endsection