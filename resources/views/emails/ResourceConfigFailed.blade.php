@extends ('emails.layouts.NodeError', [ 'retryUrl' => $retryUrl, 'node' => $node ])

@section ('error')
    <p>The daemon config does not match the expected values. Details:</p>

    <h3>Reason:</h3>

    <ul class="errors">
    @foreach ($errors as $error)

        @foreach ($error as $message)
                <li>{{$message}}</li>
        @endforeach

    @endforeach
    </ul>
@endsection
