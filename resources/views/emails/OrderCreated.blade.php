@extends ('emails.layouts.master')

@section ('content')
    <h2>Thank you for your order</h2>

    <p>We're delighted to confirm your order. Thank you again for trusting
        us and enjoy your new Spectero Cloud purchase.</p>

    <p>Have questions or comments? Email us at info@spectero and
        we'll be happy to help you with anything we can.</p>

    <a target="_blank" href="{{ $url }}">View Order</a>
@endsection