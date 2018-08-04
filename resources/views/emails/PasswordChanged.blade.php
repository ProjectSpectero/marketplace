@extends ('emails.layouts.master')

@section ('content')
    <p>Hi,</p>
    <p>Someone (hopefully you) requested that your {{ env('COMPANY_NAME', 'Spectero') }} password be reset. This request has been processed and your password has been successfully reset.</p>

    <p><b>If you didn't make this request then please contact our support team immediately.</b></p>

    <p>Otherwise, you may now login to our cloud panel using your new password by clicking the link below.</p>

    <a class="btn" target="_blank" href="{{ $loginUrl }}">Login Now</a>
    
    <p>This request was made from the IP address <b>{{ $requestIp }}</b>.</p>
@endsection