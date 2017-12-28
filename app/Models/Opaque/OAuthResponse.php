<?php


namespace App\Models\Opaque;


class OAuthResponse extends OpaqueBase
{
    public $accessToken;
    public $refreshToken;
    public $success;
    public $expiry;
}