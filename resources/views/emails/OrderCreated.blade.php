@extends ('emails.layouts.master')

@section ('content')
    <h2>Thank You For Your Order</h2>

    <p>Hi {{ $order->user->name }},</p>
    <p>We're delighted to confirm that we've received your order. Thank you again for trusting Spectero with your purchase.</p>

    <p><b>Your order requires you to complete payment before activation.</b> Please click the link below to proceed to payment.</p>
    <a class="btn" target="_blank" href="{{ $url }}">View Order &amp; Pay</a>

    <p>Have questions or comments? Email us at {{ env('COMPANY_EMAIL', 'hello@spectero.com') }} and we'll be happy to help you with anything we can.</p>
@endsection