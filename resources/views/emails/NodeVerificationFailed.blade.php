@extends ('emails.layouts.NodeError', [ 'retryUrl' => $retryUrl, 'node' => $node ])

@section ('error')
    <p><strong>Error: </strong> {{ $error }}</p>
    <p>Please correct your details, verify that the node is reachable from the global internet and retry verification.</p>
@endsection