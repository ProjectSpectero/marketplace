@extends ('emails.layouts.NodeError', ['retryUrl' => $retryUrl])

@section ('error')

    <p>Proxy verification failed</p>

    <p>Reason: <span> {{ $error }} </span></p>

@endsection