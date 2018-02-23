@extends ('emails.layouts.master')

@section ('content')
    <h2>Thank you for your order</h2>

    <p>We're delighted to confirm that we've received order. Thank you again for trusting
        us with your purchase.</p>

    @if($order->status == \App\Constants\OrderStatus::MANUAL_FRAUD_CHECK)
        <p>Unfortunately however, it appears that the order requires manual review from our staff. We will be in touch shortly about this.</p>
        <p>A ticket has been raised in our portal to track this event.</p>
    @elseif($order->status == \App\Constants\OrderStatus::PENDING)
        <p>You can now proceed to payment (please click 'View Order' below)</p>
    @endif

    <p>Have questions or comments? Email us at {{ env('COMPANY_EMAIL', 'hello@spectero.com') }} and
        we'll be happy to help you with anything we can.</p>

    <a target="_blank" href="{{ $url }}">View Order</a>
@endsection