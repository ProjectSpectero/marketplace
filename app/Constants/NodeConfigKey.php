<?php


namespace App\Constants;


class NodeConfigKey
{
    const SystemId = "sys.id";
    const CloudConnectStatus = "cloud.connect.status";
    const HttpConfig = "http.config";
    const AuthPasswordCost = "auth.password.cost";
    const CryptoJwtKey = "crypto.jwt.key";
    const CryptoCAPassword = "crypto.ca.password";
    const CryptoServerPassword = "crypto.server.password";
    const CryptoCABlob = "crypto.ca.blob";
    const CryptoServerBlob = "crypto.server.blob";
    const CryptoServerChain = "crypto.server.chain";
    const OpenVPNListeners = "vpn.openvpn.config.listeners";
    const OpenVPNBaseConfig = "vpn.openvpn.config.template";
}