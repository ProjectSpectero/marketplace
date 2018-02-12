
<!--
  Invoice template by invoicebus.com
  To customize this template consider following this guide https://invoicebus.com/how-to-create-invoice-template/
  This template is under Invoicebus Template License, see https://invoicebus.com/templates/license/
-->

<head>
    <meta charset="utf-8">
    <title>Easy (corporate)</title>

    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="description" content="Invoicebus Invoice Template">
    <meta name="author" content="Invoicebus">

    <meta name="template-hash" content="91216e926eab41d8aa403bf4b00f4e19">

    {{--<link rel="stylesheet" href="css/template.css">--}}
</head>
<style>
    /*! Invoice Templates @author: Invoicebus @email: info@invoicebus.com @web: https://invoicebus.com @version: 1.0.0 @updated: 2015-03-27 14:03:24 @license: Invoicebus */
    /* Reset styles */
    @import url("https://fonts.googleapis.com/css?family=Open+Sans:400,400italic,700&subset=cyrillic,cyrillic-ext,latin,greek-ext,greek,latin-ext,vietnamese");
    @import url("https://fonts.googleapis.com/css?family=Sanchez&subset=latin,latin-ext");
    html, body, div, span, applet, object, iframe,
    h1, h2, h3, h4, h5, h6, p, blockquote, pre,
    a, abbr, acronym, address, big, cite, code,
    del, dfn, em, img, ins, kbd, q, s, samp,
    small, strike, strong, sub, sup, tt, var,
    b, u, i, center,
    dl, dt, dd, ol, ul, li,
    fieldset, form, label, legend,
    table, caption, tbody, tfoot, thead, tr, th, td,
    article, aside, canvas, details, embed,
    figure, figcaption, footer, header, hgroup,
    menu, nav, output, ruby, section, summary,
    time, mark, audio, video {
        margin: 0;
        padding: 0;
        border: 0;
        font: inherit;
        font-size: 100%;
        vertical-align: baseline;
    }

    html {
        line-height: 1;
    }

    ol, ul {
        list-style: none;
    }

    table {
        border-collapse: collapse;
        border-spacing: 0;
    }

    caption, th, td {
        text-align: left;
        font-weight: normal;
        vertical-align: middle;
    }

    q, blockquote {
        quotes: none;
    }
    q:before, q:after, blockquote:before, blockquote:after {
        content: "";
        content: none;
    }

    a img {
        border: none;
    }

    article, aside, details, figcaption, figure, footer, header, hgroup, main, menu, nav, section, summary {
        display: block;
    }

    /* Invoice styles */
    /**
     * DON'T override any styles for the <html> and <body> tags, as this may break the layout.
     * Instead wrap everything in one main <div id="container"> element where you may change
     * something like the font or the background of the invoice
     */
    html, body {
        /* MOVE ALONG, NOTHING TO CHANGE HERE! */
    }

    /**
     * IMPORTANT NOTICE: DON'T USE '!important' otherwise this may lead to broken print layout.
     * Some browsers may require '!important' in oder to work properly but be careful with it.
     */
    .clearfix {
        display: block;
        clear: both;
    }

    .x-hidden {
        display: none !important;
    }

    .hidden {
        display: none;
    }

    b, strong, .bold {
        font-weight: bold;
    }

    #container {
        font: normal 13px/1.4em 'Open Sans', Sans-serif;
        margin: 0 auto;
        min-height: 1158px;
        position: relative;
    }

    .left-stripes {
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        width: 100px;
        background: url("../img/stripe-bottom.png") center bottom no-repeat, url("../img/stripe-bg.jpg") repeat;
    }
    .left-stripes .circle {
        -moz-border-radius: 50%;
        -webkit-border-radius: 50%;
        border-radius: 50%;
        background: #415472;
        width: 30px;
        height: 30px;
        position: absolute;
        left: 33%;
    }
    .left-stripes .circle.c-upper {
        top: 440px;
    }
    .left-stripes .circle.c-lower {
        top: 690px;
    }

    .right-invoice {
        padding: 40px 30px 40px 130px;
        min-height: 1078px;
    }

    #memo .company-info {
        float: left;
    }
    #memo .company-info div {
        font-size: 28px;
        text-transform: uppercase;
        min-width: 20px;
        line-height: 1em;
    }
    #memo .company-info span {
        font-size: 12px;
        color: #858585;
        display: inline-block;
        min-width: 20px;
    }
    #memo .logo {
        float: right;
        margin-left: 15px;
    }
    #memo .logo img {
        width: 150px;
        height: 100px;
    }
    #memo:after {
        content: '';
        display: block;
        clear: both;
    }

    #invoice-title-number {
        margin: 50px 0 20px 0;
        display: inline-block;
        float: left;
    }
    #invoice-title-number .title-top {
        font-size: 15px;
        margin-bottom: 5px;
    }
    #invoice-title-number .title-top span {
        display: inline-block;
        min-width: 20px;
    }
    #invoice-title-number .title-top #number {
        text-align: right;
        float: right;
        color: #858585;
    }
    #invoice-title-number .title-top:after {
        content: '';
        display: block;
        clear: both;
    }
    #invoice-title-number #title {
        display: inline-block;
        background: #415472;
        color: white;
        font-size: 50px;
        padding: 7px;
        font-family: Sanchez, Serif;
        line-height: 1em;
    }

    #client-info {
        float: right;
        text-align: right;
        margin-top: 50px;
        min-width: 220px;
    }
    #client-info .client-name {
        font-weight: bold;
        font-size: 15px;
        text-transform: uppercase;
        margin: 7px 0;
    }
    #client-info > div {
        margin-bottom: 3px;
        min-width: 20px;
    }
    #client-info span {
        display: block;
        min-width: 20px;
    }
    #client-info > span {
        text-transform: uppercase;
        color: #858585;
        font-size: 15px;
    }

    table {
        table-layout: fixed;
    }
    table th, table td {
        vertical-align: top;
        word-break: keep-all;
        word-wrap: break-word;
    }

    #invoice-info {
        float: left;
        margin-top: 10px;
    }
    #invoice-info div {
        margin-bottom: 3px;
    }
    #invoice-info div span {
        display: inline-block;
        min-width: 20px;
        min-height: 18px;
    }
    #invoice-info div span:first-child {
        font-weight: bold;
        text-transform: uppercase;
        margin-right: 10px;
    }
    #invoice-info:after {
        content: '';
        display: block;
        clear: both;
    }

    .currency {
        margin-top: 20px;
        text-align: right;
        color: #858585;
        font-style: italic;
        font-size: 12px;
    }
    .currency span {
        display: inline-block;
        min-width: 20px;
    }

    #items {
        margin-top: 10px;
    }
    #items .first-cell, #items table th:first-child, #items table td:first-child {
        width: 18px;
        text-align: right;
    }
    #items table {
        border-collapse: separate;
        width: 100%;
    }
    #items table th {
        font-family: Sanchez, Serif;
        font-size: 12px;
        text-transform: uppercase;
        padding: 5px 3px;
        text-align: right;
        background: #b0b4b3;
        color: white;
    }
    #items table th:nth-child(2) {
        width: 30%;
        text-align: left;
    }
    #items table th:last-child {
        text-align: right;
    }
    #items table td {
        padding: 10px 3px;
        text-align: right;
        border-bottom: 1px solid #ddd;
    }
    #items table td:first-child {
        text-align: left;
    }
    #items table td:nth-child(2) {
        text-align: left;
    }

    #sums {
        float: right;
        margin-top: 30px;
    }
    #sums table tr th, #sums table tr td {
        min-width: 100px;
        padding: 8px 3px;
        text-align: right;
    }
    #sums table tr th {
        padding-right: 25px;
    }
    #sums table tr.amount-total td {
        background: #415472;
        color: white;
        font-family: Sanchez, Serif;
        font-size: 35px;
        line-height: 1em;
        padding: 7px !important;
    }
    #sums table tr.due-amount th, #sums table tr.due-amount td {
        font-weight: bold;
    }

    #terms {
        margin-top: 60px;
    }
    #terms > span {
        font-weight: bold;
        display: inline-block;
        min-width: 20px;
        text-transform: uppercase;
    }
    #terms > div {
        min-height: 50px;
        min-width: 50px;
    }

    .payment-info {
        font-size: 12px;
        color: #858585;
        margin-top: 30px;
    }
    .payment-info div {
        min-width: 20px;
    }
    .payment-info div:first-child {
        font-weight: bold;
    }

    .ib_invoicebus_fineprint {
        text-align: left !important;
        padding-left: 130px !important;
        width: auto !important;
    }

    /**
     * If the printed invoice is not looking as expected you may tune up
     * the print styles (you can use !important to override styles)
     */
    @media print {
        /* Here goes your print styles */
    }

</style>
<body>

@if ($invoice->status == \App\Constants\InvoiceStatus::PAID)
    <p>Thank you for your purchase</p>

    <p>This is an automatically generated message to confirm receipt of your order</p>
    <p>You do not need to reply to this e-mail, but you may wish to save it for your records.</p>

    <p>Bellow you will find a copy of the invoice of your recent order</p>
@endif

<div id="container">
    <div class="left-stripes">
        <div class="circle c-upper"></div>
        <div class="circle c-lower"></div>
    </div>

    <div class="right-invoice">
        <section id="memo">
            <div class="company-info">
                <div>{{ env('LEGAL_COMPANY_NAME') }}</div>
                <br>
                <span>{{ env('LEGAL_COMPANY_ADDRESS_PARTIAL_1') }}</span>
                <span>{{ env('LEGAL_COMPANY_ADDRESS_PARTIAL_2') }}</span>
                <br>
            </div>

            <div class="logo">
                <img src="{{ url('images/logo-dark.png') }}" />
            </div>
        </section>

        <section id="invoice-title-number">

            <div class="title-top">
                <span class="x-hidden">Date</span>
                <span>{{ \Carbon\Carbon::now() }}</span> <span id="number">{{ $invoice->id }}</span>
            </div>

            <div id="title">Invoice # {{ $invoice->id }}</div>

        </section>

        <section id="client-info">
            <span>Bill to:</span>
            <div class="client-name">
                <span>{{ $invoice->order->user->name }}</span>
            </div>

            <div>
                <span>{{ $userAddress }}</span>
            </div>

            <div>
                <span>{{ $organization }}</span>
            </div>

            <div>
                <span>{{ $invoice->order->user->email }}</span>
            </div>

        </section>

        <div class="clearfix"></div>

        {{--<section id="invoice-info">--}}
            {{--<div>--}}
                {{--<span>{net_term_label}</span> <span>{net_term}</span>--}}
            {{--</div>--}}
            {{--<div>--}}
                {{--<span>{due_date_label}</span> <span>{due_date}</span>--}}
            {{--</div>--}}
            {{--<div>--}}
                {{--<span>{po_number_label}</span> <span>{po_number}</span>--}}
            {{--</div>--}}
        {{--</section>--}}

        <div class="clearfix"></div>

        <div class="currency">
            <span>Currency: </span> <span>{{ $invoice->currency }}</span>
        </div>

        <section id="items">

            <table cellpadding="0" cellspacing="0">

                <tr>
                    <th>No.</th> <!-- Dummy cell for the row number and row commands -->
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Amount</th>
                </tr>

                @foreach ($lineItems as $index => $item)
                    <tr data-iterate="item">
                        <td>{{ ++$index }}</td> <!-- Don't remove this column as it's needed for the row commands -->
                        <td>{{ $item->description }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->amount }}</td>
                    </tr>
                @endforeach

            </table>

        </section>

        <section id="sums">

            <table cellpadding="0" cellspacing="0">

                <tr class="amount-total">
                    <!-- {amount_total_label} -->
                    <td colspan="2">{{ $invoice->amount }} {{ $invoice->currency }}</td>
                </tr>

                <!-- You can use attribute data-hide-on-quote="true" to hide specific information on quotes.
                     For example Invoicebus doesn't need amount paid and amount due on quotes  -->
                {{--<tr data-hide-on-quote="true">--}}
                    {{--<th>{amount_paid_label}</th>--}}
                    {{--<td>{amount_paid}</td>--}}
                {{--</tr>--}}

                <tr data-hide-on-quote="true" class="due-amount">
                    <th>Due Date:</th>
                    <td>12/12/22</td>
                </tr>

            </table>

        </section>

        <div class="clearfix"></div>

        <section id="terms">

            <span>Invoice status:</span>
            <div>{{ $invoice->status }}</div>

        </section>

        {{--<div class="payment-info">--}}
            {{--<div>{payment_info1}</div>--}}
            {{--<div>{payment_info2}</div>--}}
            {{--<div>{payment_info3}</div>--}}
            {{--<div>{payment_info4}</div>--}}
            {{--<div>{payment_info5}</div>--}}
        {{--</div>--}}

        <div id="items">
            <h2 style="text-align:center">Transactions</h2> <br />
            @if(count($transactions) > 0)
                <table>
                    <tr>
                        <th>No.</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Provider</th>
                        <th>Amount</th>
                        <th>Currency</th>
                    </tr>
                    @foreach ($transactions as $index => $transaction)
                        <tr>
                            <td> {{ ++$index }}</td>
                            <td>{{ $transaction->type }}</td>
                            <td>{{ $transaction->reference }}</td>
                            <td>{{ $transaction->provider }}</td>
                            <td>{{ $transaction->amount }}</td>
                            <td>{{ $transaction->currency }}</td>
                        </tr>
                    @endforeach
                </table>
            @else
                No transactions could be found for this invoice. <br />
            @endif
        </div>
    </div>
</div>

{{--<script src="http://cdn.invoicebus.com/generator/generator.min.js?data=data.js"></script>--}}
</body>

