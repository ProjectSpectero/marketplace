@extends ('emails.layouts.master')

@section ('content')
    <p>Hi there,</p>
    <p>It appears that we were unable to automatically charge your saved payment method for your Spectero invoice <b>#{{ $invoice->id }}</b>.</p>
    <p>Please look into why this might have happened. Insufficient funds or a temporary hold by your financial institution are the common causes.</p>

    @if($reason != null)
        <p>Error from our payment processor: <b>{{ $reason }}</b></p>
    @endif

    <p>In the meantime, please consider <a class="no-style" href="{{ $manualUrl }}">making a payment now</a> and updating the stored payment method to avoid this issue in the future.</p>

    <a target="_blank" href="{{ $manualUrl }}">Make A Payment Now</a>
@endsection