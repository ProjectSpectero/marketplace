@extends ('emails.layouts.master')

@section ('content')
    <h2>Node Verification Successful</h2>

    <p>Hi {{ $node->user->name }},</p>
    <p>Congratulations! This email is to let you know that we have successfully verified your Spectero node <b>#{{ $node->id }}</b> with IP address <b>{{ $node->ip }}</b></p>

    <p>You may now update it and decide whether you wish to list it on our marketplace alongside other relevant parameters.</p>
    <a target="_blank" href="{{ $nodeUrl }}">View Node</a>
@endsection