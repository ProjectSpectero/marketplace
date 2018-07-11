@extends ('emails.layouts.master')

@section ('content')
    <h2>Email Changed</h2>

    <p>Hi,</p>
    <p>It looks like you changed your email address for your Spectero account. We sent instructions for verifying your new email to:</p>
    <p><b>{{ $newEmail }}</b></p>

    <p>If you didn't request this, please contact our support team immediately.</p>
@endsection