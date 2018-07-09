@extends ('emails.layouts.master')

@section ('content')
    <h2>Thank You For Your Payment</h2>

    <p>Hi {{ $invoice->user->name }},</p>
    <p>This is an automatic message to confirm receipt of your payment on your Spectero invoice <b>#{{ $invoice->id }}</b>.</p>
    <p>You do not need to reply to this e-mail, but you may save it for your records.</p>

    <p>
        <b>{{ $transaction->amount }} {{ $transaction->currency }}</b> was {{ strtolower($transaction->type) }}ed via your
        @if($transaction->payment_processor == \App\Constants\PaymentProcessor::STRIPE)
            Credit Card
        @else
                {{ $transaction->payment_processor }} account.
        @endif
    </p>

    @if($due <= 0)
        <p>This invoice has been paid in full. Any associated orders have also been marked for activation.</p>
    @else
        <p>This payment has been recorded as a partial payment. This invoice now has an outstanding due of <b>{{ $due }} {{ $invoice->currency }}</b>.</p>
    @endif

    <p>You can view an online copy of the invoice by clicking the link below:</p>
    <a target="_blank" href="{{ $invoiceUrl }}">View Invoice</a>
@endsection

