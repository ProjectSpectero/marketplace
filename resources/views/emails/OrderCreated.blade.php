@extends ('emails.layouts.master')

@section ('content')
    <h2>Thank You For Your Order</h2>

    <p>Hi {{ $order->user->name }},</p>
    <p>We're delighted to confirm that we've received your order. Thank you again for trusting Spectero with your purchase.</p>

    @if($order->status == \App\Constants\OrderStatus::MANUAL_FRAUD_CHECK)
        <p><b>Unfortunately it appears that the order requires manual review from our staff.</b></p>
        <p>Our verification team will be in touch shortly about this. A support ticket has been created in our portal to track this event.</p>
        <a class="btn" target="_blank" href="{{ $url }}">View Order</a>
    @elseif($order->status == \App\Constants\OrderStatus::PENDING)
        <p><b>Your order requires you to complete payment before activation.</b> Please click the link below to proceed to payment:</p>
        <a class="btn" target="_blank" href="{{ $url }}">View Order &amp; Pay</a>
    @endif

    <p>Have questions or comments? Email us at {{ env('COMPANY_EMAIL', 'hello@spectero.com') }} and we'll be happy to help you with anything we can.</p>
@endsection