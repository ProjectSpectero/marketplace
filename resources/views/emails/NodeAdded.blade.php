@extends ('emails.layouts.master')

@section ('content')
    <h2>New Node Added</h2>

    <p>Hi {{ $node->user->name }},</p>
    <p>This email is to let you know that we have added a new Spectero node <b>#{{ $node->id }}</b> with IP address <b>{{ $node->ip }}</b> to your account.</p>
    <p>Our automated system(s) will now attempt to verify connectivity to it. You will be emailed with the results once this process completes.</p>
    <p>In the meantime, <a href="https://spectero.atlassian.net/wiki/spaces/docs/pages/4587523/Connectivity+Requirements">please review our connectivity guidelines to ensure that this system meets the requirements.</a></p>

    <p>You can also manage this node (in limited capacity until it is verified) via our Cloud portal.</p>
    <a class="btn" target="_blank" href="{{ $nodeUrl }}">Manage Node</a>
@endsection