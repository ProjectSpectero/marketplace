@extends ('emails.layouts.master')

@section ('content')

    <style>
        .container, .content {
            max-width: 100%;
            width: 100%;
        }

        .testz {
            display: none;
        }
    </style>

    <div class="text-wrapper">
        <p>Thank you for your purchase</p>

        <p>This is an automatically generated message to confirm receipt of your order</p>
        <p>You do not need to reply to this e-mail, but you may wish to save it for your records.</p>

        <p>Bellow you will find a copy of the invoice of your recent order</p>
    </div>

    {!! $invoice !!}

@endsection

