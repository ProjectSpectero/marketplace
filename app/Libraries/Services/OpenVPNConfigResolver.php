<?php

namespace App\Libraries\Services;
use App\Node;
use App\Order;

class OpenVPNConfigResolver
{
    public static function resolveOrderConfig (Node $node, Order $order)
    {
        $hash = "openvpn.config" . sha1($node->id . $order->id . $node->updated_at . $order->updated_at);

        if (\Cache::has($hash))
            return \Cache::get($hash);

        $caData = $node->getCertificate('ca');
        list ($userIdentifier, $password) = explode(':', $order->accessor);
        $systemId = $node->install_id;

        // This is a JSON encoded string, gotta decode.
        $listenerString = $node->getConfigKey(\App\Constants\NodeConfigKey::OpenVPNListeners);

        $listeners = json_decode($listenerString, true);
        $parsedListeners = [];

        foreach ($listeners as $listener)
        {
            $protocol = null;

            if ($listener['Protocol'] == 'TCP')
                $protocol = 'tcp-client';
            else
                $protocol = 'udp';

            // This translates IPAddress.Any (0.0.0.0) to an actually routable IP
            // TODO: Consider impacts of IPv6 here.
            if($listener['IPAddress'] == '0.0.0.0')
                $listener['IPAddress'] = $node->ip;

            $parsedListeners[] = [ 'ip' => $listener['IPAddress'], 'port' => $listener['Port'], 'protocol' => $protocol ];
        }

        usort($parsedListeners, function($itemOne, $itemTwo)
        {
            if ($itemOne['protocol'] == 'udp')
                return -1;

            return 1;
        });

        // TODO: Cache this in the DB on a per lineItem, node basis. There is no reason to generate it multiple times. The CA cert is not expected to change
        $certificate = \App\Libraries\PKIManager::issueUserChain($caData, $userIdentifier);

        $data = view('templates.openvpnuser', [
            'username' => $userIdentifier,
            'systemId' => $systemId,
            'listeners' => $parsedListeners,
            'chunkedCert' => rtrim(chunk_split($certificate, 64, PHP_EOL)),
            'cipherType' => 'AES-256-CBC' // TODO: Allow this to be dynamically configurable someday.
        ])->render();

        \Cache::put($hash, $data, env('OVPN_GENERATED_CONF_CACHE_MINUTES', 1440));

        return $data;
    }
}