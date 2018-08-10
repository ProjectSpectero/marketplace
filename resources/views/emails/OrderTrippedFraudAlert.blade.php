@extends ('emails.layouts.master')

@section ('content')
    <h2>Order Verification Failed</h2>

    <p>Hi {{ $order->user->name }},</p>
    <p>We're sorry to inform you that this order failed our automatic verification.</p>
    <p>Unfortunately it appears that the order <b>requires manual review from our staff.</b> </p>
    <p>Our verification team will be in touch shortly about this. A support ticket has been created in our portal to track this event.</p>

    <a class="btn" target="_blank" href="{{ $url }}">View Order</a>

    <p>Have questions or comments? Email us at {{ env('COMPANY_EMAIL', 'hello@spectero.com') }} and we'll be happy to help you with anything we can.</p>
@endsection