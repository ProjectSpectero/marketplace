@extends ('emails.layouts.master')

@section ('content')

    <p>Hi there,</p>
    <p>This email is to remind you that payment is pending for invoice #{{ $invoice->id }}.</p>

    <p>To ensure smooth operation your service(s), please make sure to submit payment in time.</p>

    <p>Please make <a class="no-style" href="{{ $manualUrl }}">a payment manually</a> or
        update the stored payment method to avoid this issue in the future.</p>

@endsection