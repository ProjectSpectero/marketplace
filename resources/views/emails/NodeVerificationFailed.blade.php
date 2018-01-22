@extends ('emails.layouts.master')

@section ('content')

    <h2>Node Verification Failed</h2>

    <p><strong>ERROR: </strong> {{ $error }}</p>

@endsection