@extends ('emails.layouts.master')

@section ('content')

    <p>Hi there,</p>
    <p>Someone (hopefully you) requested earlier that their {{ env('COMPANY_NAME', 'Spectero') }} password be reset from the IP address {{ $requestIp }}.</p>

    <p>This request has been processed, your password has been successfully reset.</p>

    <p>You can now use your new password to login to our <a href="{{ $loginUrl }}">portal here.</a></p>
@endsection