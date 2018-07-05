@extends ('emails.layouts.NodeError', [ 'retryUrl' => $retryUrl, 'node' => $node ])

@section ('error')
    <p>One or more proxies configured in the HTTPProxy service failed validation.</p>
    <p>Reason: <b>{{ $error }}</b></p>
@endsection