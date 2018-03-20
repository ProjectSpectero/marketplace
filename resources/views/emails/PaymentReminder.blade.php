@extends ('emails.layouts.master')

@section ('content')

    <p>Hi there,</p>
    <p>It appears that you have an overdue payment for invoice #{{ $invoice->id }}.</p>

    <p>Please make <a class="no-style" href="{{ $manualUrl }}">a payment manually</a> or
        update the stored payment method to avoid this issue in the future.</p>

@endsection