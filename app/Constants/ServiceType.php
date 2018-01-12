<?php


namespace App\Constants;


class ServiceType extends Holder
{
    const HTTPProxy = "HTTPProxy";
    const OpenVPN = "OpenVPN";
    const SSHTunnel = "SSHTunnel";
    const ShadowSOCKS = "ShadowSOCKS";
}