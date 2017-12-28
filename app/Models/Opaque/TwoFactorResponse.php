<?php


namespace App\Models\Opaque;


class TwoFactorResponse extends OpaqueBase
{
    public $userId;
    public $twoFactorToken;
}