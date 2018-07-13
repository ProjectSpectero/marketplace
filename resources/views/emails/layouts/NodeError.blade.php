@extends ('emails.layouts.master')

@section ('content')
    <h2>Node Verification Failed</h2>

    <p>Hi {{ $node->user->name }},</p>
    <p>This email is to notify you that we were unable to verify your Spectero node <b>#{{ $node->id }}</b> with IP address <b>{{ $node->ip }}</b>.</p>

    @yield ('error')

    <p>If you've resolved the issue, you're welcome to retry verification at any time. Simply click the link below to retry verification:</p>
    <a class="btn" target="_blank" href="{{ $retryUrl }}">Retry Verification</a>

@endsection