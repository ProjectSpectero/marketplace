@extends ('emails.layouts.master')

@section ('content')

    <p>Hi there,</p>
    <p>This email is to let you know that a new invoice (#{{ $invoice->id }}) has been generated {{ $reason }}</p>
    <p>We generate invoices {{ env("EARLY_INVOICE_GENERATION_DAYS", 14) }} days early to give you ample time to arrange payment. This invoice is due on {{ $invoice->due_date }}</p>

    @if($invoice->type != \App\Constants\InvoiceType::CREDIT)
        <p>If you have ample account credit, or a stored (valid) credit card on file, payment will automatically be processed on the due date.</p>
    @endif

    <p>To ensure smooth operation your service(s), please make sure to submit payment in time.</p>

    <p>Please make <a class="no-style" href="{{ $manualUrl }}">a payment manually</a> or
        update the stored payment method to automatically submit payment in the future.</p>

@endsection