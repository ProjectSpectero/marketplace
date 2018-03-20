@extends ('emails.layouts.master')

@section ('content')

    <p>Hi there,</p>
    <p>It appears that we were unable to automatically charge your saved payment method for Invoice #{{ $invoice->id }}.</p>
    <p>Please look into why this might have happened (insufficient funds or a temporary hold by your financial institution are the common causes).</p>

    @if($reason != null)
        <p>Gateway error: {{ $reason }}</p>
    @endif

    <p>In the meantime, please consider<a class="no-style" href="{{ $manualUrl }}"> submitting a payment manually</a> and
    updating the stored payment method to avoid this issue in the future.</p>

@endsection