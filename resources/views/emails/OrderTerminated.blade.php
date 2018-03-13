@extends ('emails.layouts.master')

@section ('content')
    <h2>Your order has been terminated</h2>

    <p>We're sorry to inform you that your order has been terminated.</p>
    <p>Reason: Order overdue passed </p>

    <p>Have questions or comments? Email us at {{ env('COMPANY_EMAIL', 'hello@spectero.com') }} and
        we'll be happy to help you with anything we can.</p>

    <a target="_blank" href="{{ $url }}">View Order</a>
@endsection