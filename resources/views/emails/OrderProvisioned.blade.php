@extends ('emails.layouts.master')

@section ('content')
<h2>Your Order Has Been Provisioned</h2>

<p>We're delighted to inform you that your order #{{ $order->id }} has now finished being provisioned.</p>

<p>This means that your ordered service(s) / resource(s) should now available for use.</p>

<p>In case you purchased access to a node or group offered by one of our marketplace sellers, you may have to wait a few moments more for synchronization to complete.
    This is tracked on the order page, and you can view live status updates there.</p>

<a target="_blank" href="{{ $url }}">Please click here to view your order.</a>

<p>Have questions or comments? Email us at {{ env('COMPANY_EMAIL', 'hello@spectero.com') }} and
    we'll be happy to help you with anything we can.</p>

@endsection