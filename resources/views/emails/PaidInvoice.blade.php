@extends ('emails.layouts.master')

@section ('content')

    <h2>Thank you for your purchase!</h2>

    <p>This is an automatically generated message to confirm receipt of your payment on Invoice #{{ $invoice->id }}</p>
    <p>You do not need to reply to this e-mail, but you may wish to save it for your records.</p>

    <p>
        {{ $transaction->amount }} {{ $transaction->currency }} was {{ $transaction->type }}ed via your
        @if($transaction->payment_processor == \App\Constants\PaymentProcessor::STRIPE)
            Credit Card
        @else
                {{ $transaction->payment_processor }} account.
        @endif
    .</p>

    <p>You can view an online copy of the invoice by clicking the button below.</p>

    <a target="_blank" href="{{ $invoiceUrl }}">View Invoice</a>

@endsection

