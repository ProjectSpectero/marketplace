<?php


namespace App\Models\Opaque;


class OpaqueBase implements OpaqueInterface
{
    public function toArray(): array
    {
        return \json_decode($this->toJson(), true);
    }

    public function toJson(): String
    {
        return \json_encode($this);
    }
}