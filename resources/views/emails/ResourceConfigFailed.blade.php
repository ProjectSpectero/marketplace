@extends ('emails.layouts.NodeError', ['retryUrl' => $retryUrl])

@section ('error')

    <p>The service could not be created</p>

    <p>The daemon config does not match the expected values. See the reason bellow</p>

    <h3>Reason:</h3>

    <ul class="errors">
    @foreach ($errors as $error)

        @foreach ($error as $message)
                <li>{{$message}}</li>
        @endforeach

    @endforeach
    </ul>

@endsection
