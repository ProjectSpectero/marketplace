@extends ('emails.layouts.master')

@section ('content')

    <p>Your password has been successfully reset</p>

    <p>New Password: {{ $newPassword }}</p>

@endsection