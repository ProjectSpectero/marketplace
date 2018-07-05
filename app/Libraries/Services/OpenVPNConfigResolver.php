<?php

namespace App\Libraries\Services;
use App\Node;
use App\Order;

class OpenVPNConfigResolver
{
    public static function resolveOrderConfig (Node $node, Order $order)
    {
        $hash = sha1($node->id . $order->id . $node->updated_at . $order->updated_at);

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

            $parsedListeners[] = [ 'ip' => $listener['IPAddress'], 'port' => $listener['Port'], 'protocol' => $protocol ];
        }

        // TODO: Cache this in the DB on a per lineItem, node basis. There is no reason to generate it multiple times. The CA cert is not expected to change
        $certificate = \App\Libraries\PKIManager::issueUserChain($caData, $userIdentifier);

        $data = view('templates.openvpnuser', [
            'username' => $userIdentifier,
            'systemId' => $systemId,
            'listeners' => $parsedListeners,
            'chunkedCert' => chunk_split($certificate, 64, PHP_EOL),
            'cipherType' => 'AES-256-CBC' // TODO: Allow this to be dynamically configurable someday.
        ])->render();

        \Cache::put($hash, $data, env('OVPN_GENERATED_CONF_CACHE_MINUTES', 1440));

        return $data;
    }
}