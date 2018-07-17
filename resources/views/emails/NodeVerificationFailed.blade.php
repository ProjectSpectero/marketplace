@extends ('emails.layouts.NodeError', [ 'retryUrl' => $retryUrl, 'node' => $node ])

@section ('error')
    <p><strong>Error: </strong> {!! $error !!}</p>
    <p>Please correct your details, verify that the node is reachable from the global internet and retry verification.</p>
    <p>For a listing of commonly encountered issues (along with their solutions), <a href="https://spectero.atlassian.net/wiki/spaces/docs/pages/3899396/Common+Issues+and+Their+Solutions">please review our documentation.</a></p>
@endsection