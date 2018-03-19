@extends ('emails.layouts.master')

@section ('content')

    <p>Hi there,</p>
    <p>it seems like we where unable to process your invoice #{{ $invoice->id }}.</p>

    <p>Please either pay it <a class="no-style" href="{{ $manualUrl }}">manually</a> or
    add store a card to avoid termination in future payments. </p>

@endsection