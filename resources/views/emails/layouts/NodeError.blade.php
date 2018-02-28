@extends ('emails.layouts.master')

@section ('content')
    <h2>Node Verification Failed!</h2>

    <p>Hi {{ $node->user->name }}</p>
    <p>This email is to let you know that we have attempted to verify your node #{{ $node->id }} with IP {{ $node->ip }} ({{ $node->friendly_name }}), but were unable to do so.</p>

    @yield ('error')

    <p>If you've resolved the issue, you're welcome to retry any time. Simply press the button below to get started.</p>
    <a target="_blank" href="{{ $retryUrl }}">Retry</a>

@endsection