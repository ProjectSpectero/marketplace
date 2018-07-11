@extends ('emails.layouts.master')

@section ('content')
    <h2>Your Order Has Been Terminated</h2>

    <p>Hi {{ $order->user->name }},</p>
    <p>We're sorry to inform you that your Spectero order has been terminated.</p>
    <p><b>Reason:</b> Payment was not received in time.</p>

    <a target="_blank" href="{{ $url }}">View Order</a>

    <p>Have questions or comments? Email us at {{ env('COMPANY_EMAIL', 'hello@spectero.com') }} and we'll be happy to help you with anything we can.</p>
@endsection