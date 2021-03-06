@extends ('emails.layouts.master')

@section ('content')
    <p>Hi,</p>
    <p>Welcome to Spectero! We're so glad to have you on our platform.</p>

    @if(! $easy)
        <p>Please verify your email by clicking the link below.</p>
        <a class="btn" target="_blank" href="{{ $url }}">Verify My Account</a>
    @else
        <p>You need to choose a password to finish setting up your account. Please visit the link below (expires in {{ env('EASY_SIGNUP_TOKEN_EXPIRY_IN_DAYS', 10) }} days).</p>
        <a class="btn" target="_blank" href="{{ $url }}">Finalize Account</a>
        <p>Please note that this link is only usable from the same computer (or device) you performed the sign-up on.</p>
    @endif
    <!--<p>If your email client is unable to click on the above link, please copy and paste ({{ $url }}) into your browser instead.</p>-->
    <p>Once you complete this process, you'll be able to login and access the platform (and any purchased services).</p>
    <p>Have questions or comments? Email us at {{ env('COMPANY_EMAIL', 'hello@spectero.com') }} and we'll be happy to help you with anything we can.</p>
@endsection