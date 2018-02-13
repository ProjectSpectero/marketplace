@extends ('emails.layouts.master')

@section ('content')

    <p>Thank you for your purchase</p>

    <p>This is an automatically generated message to confirm receipt of your order</p>
    <p>You do not need to reply to this e-mail, but you may wish to save it for your records.</p>

    <p>{{ $transaction->amount }} {{ $transaction->currency }} was transacted in your account as a
    {{ $transaction->type }} transaction via {{ $transaction->payment_processor }}</p>

    <p>Bellow you will find a copy of the invoice of your recent order</p>

    <a target="_blank" href="{{ $invoiceUrl }}">View Invoice</a>

@endsection

