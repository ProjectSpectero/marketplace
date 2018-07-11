@extends ('emails.layouts.master')

@section ('content')
    <p>Hi {{ $invoice->user->name }},</p>
    <p>This email is to remind you that payment is pending for your Spectero invoice <b>#{{ $invoice->id }}</b> which is due on <b>{{ $invoice->due_date }}</b>.</p>

    <p>To ensure smooth operation your services, please make sure to submit payment in time.</p>

    <p>Please <a target="_blank" href="{{ $manualUrl }}">make a payment now</a> or update your payment method in our cloud panel to be automatically billed by our system in the future.</p>

    <a class="btn" target="_blank" href="{{ $manualUrl }}">Make A Payment</a>
@endsection