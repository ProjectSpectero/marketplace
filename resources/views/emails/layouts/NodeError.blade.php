@extends ('emails.layouts.master')

@section ('content')

    @yield ('error')

    <a target="_blank" href="{{ $retryUrl }}">Retry</a>

@endsection