@extends ('emails.layouts.master')

@section ('content')
    <p>Hi {{ $invoice->user->name }},</p>
    <p>
        It appears that we were unable to automatically charge your saved payment method for your Spectero invoice <b>#{{ $invoice->id }}</b>
        which was due on <b>{{ $invoice->due_date->format(\App\Constants\DateFormat::EMAIL) }}</b>.
    </p>
    <p>Please look into why this might have happened. Insufficient funds or a temporary hold by your financial institution are the common causes.</p>

    @if($reason != null)
        <p>Error from our payment processor: <b>{{ $reason }}</b></p>
    @endif

    <p>In the meantime, please consider <a href="{{ $manualUrl }}">making a payment now</a> and updating the stored payment method to avoid this issue in the future.</p>

    <a class="btn" target="_blank" href="{{ $manualUrl }}">Make A Payment Now</a>
@endsection