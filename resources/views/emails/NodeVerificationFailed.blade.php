@extends ('emails.layouts.master')

@section ('content')
    <h2>Node Verification Failed</h2>

    <p>Hi {{ $node->user->name }}</p>
    <p>This email is to let you know that we have attempted to verify your node #{{ $node->id }} with IP {{ $node->ip }} ({{ $node->friendly_name }}), but were unable to do so.</p>
    <p><strong>ERROR: </strong> {{ $error }}</p>
    <p>Please correct your details, verify that the node is reachable from the global internet and retry the verification.</p>
    <a target="_blank" href="{{ $retryUrl }}">Retry</a>
@endsection