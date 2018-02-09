<?php


namespace App\Models\Opaque;


class PaymentProcessorResponse extends OpaqueBase
{
    public $type;
    public $subtype;
    public $method;
    public $redirectUrl;
    public $raw;
}