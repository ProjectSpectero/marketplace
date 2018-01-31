@extends ('emails.layouts.master')

@section ('content')

    <p>Hi there,</p>
    <p>Someone (hopefully you) requested earlier that their {{ env('COMPANY_NAME', 'Spectero') }} password be reset from the IP address {{ $requestIp }}.</p>

    <p>This request has been processed, your password has been successfully reset.</p>

    <p>Your new password is: {{ $newPassword }}</p>
    <p>Please use it to login to our portal at <a href="{{ $loginUrl }}">here.</a></p>

@endsection