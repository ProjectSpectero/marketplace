@extends ('emails.layouts.master')

@section ('content')
    <h2>Node Verification Succeeded!</h2>

    <p>Hi {{ $node->user->name }}</p>
    <p>Congratulations! This email is to let you know that we have successfully verified your node #{{ $node->id }} with IP {{ $node->ip }} ({{ $node->friendly_name }}).</p>
    <p>{{ $node->services->count() }} service(s) were auto-discovered.</p>
    <p>You may now update it to decide whether you wish to list it on our marketplace alongside other relevant parameters.</p>
    <a target="_blank" href="{{ $nodeUrl }}">View Node</a>
@endsection