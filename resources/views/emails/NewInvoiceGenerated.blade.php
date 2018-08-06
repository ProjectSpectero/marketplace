@extends ('emails.layouts.master')

@section ('content')
    <h2>New Invoice Generated</h2>

    <p>Hi {{ $invoice->user->name }},</p>
    <p>This email is to let you know that a new Spectero invoice (<b>#{{ $invoice->id }}</b>) has been generated {{ $reason }}. This invoice is due on <b>{{ $invoice->due_date->format(\App\Constants\DateFormat::EMAIL) }}</b>. </p>

    <p>To ensure smooth operation your service(s), please make sure to submit payment in time.</p>

    <a class="btn" target="_blank" href="{{ $manualUrl }}">View Invoice</a>

    <p>Payment can be made using the link above.
        @if($invoice->type != \App\Constants\InvoiceType::CREDIT)
            You can also set up automatic payments from the same link.
        @endif
    </p>

    @if($invoice->type == \App\Constants\InvoiceType::STANDARD)
        <p>We generate invoices {{ env("EARLY_INVOICE_GENERATION_DAYS", 14) }} days early to give you ample time to arrange payment.</p>
        <p>If you have ample account credit, or a stored valid credit card on file, payment will automatically be processed on the due date.</p>
    @elseif($invoice->type == \App\Constants\InvoiceType::MANUAL)
        <p>This invoice was manually raised, likely in response to one of your requests.</p>
    @endif
@endsection