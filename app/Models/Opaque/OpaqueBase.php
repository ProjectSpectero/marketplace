<?php


namespace App\Models\Opaque;


class OpaqueBase implements OpaqueInterface
{
    public function toArray(): array
    {
        return \json_decode(\json_encode($this), true);
    }
}