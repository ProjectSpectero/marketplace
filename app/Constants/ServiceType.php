<?php


namespace App\Constants;


class ServiceType extends Holder
{
    const HTTPProxy = "HTTPProxy";
    const OpenVPN = "OpenVPN";
    const SSHTunnel = "SSHTunnel";
    const ShadowSOCKS = "ShadowSOCKS";

    // TODO: Make this capable of getting resources from all services
    public static function getDiscoverable () : array
    {
        return [
            self::HTTPProxy,
            self::OpenVPN
        ];
    }

    public static function isDiscoverable (string $serviceType) : bool
    {
        return in_array($serviceType, self::getDiscoverable());
    }
}