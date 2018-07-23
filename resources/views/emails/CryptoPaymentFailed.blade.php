@extends ('emails.layouts.master')

@section ('content')
    <h2>Crypto Payment Failed</h2>

    <p>Hi {{ $user->name }},</p>
    <p>This is an automatic message to let you know that your attempt to submit payment via crypto currencies for invoice <b>#{{ $invoice->id }}</b> has been unsuccessful.</p>
    <p>Our payment proessing partner has notified us that this charge did not become finalized in time.</p>

    <p>You can retry by clicking the link below, you're also welcome to try with another payment provider as needed.</p>
    <a class="btn" target="_blank" href="{{ $invoiceUrl }}">View Invoice</a>
@endsection

