<?php


namespace App\Models\Opaque;


class PaymentProcessorResponse extends OpaqueBase
{
    public $type;
    public $redirectUrl;
    public $raw;
}