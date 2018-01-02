<?php


namespace App\Models\Opaque;


class TwoFactorManagementResponse extends OpaqueBase
{
    public $userId;
    public $secretCode;
    public $qrCodeUrl;
    public $backupCodes;
}