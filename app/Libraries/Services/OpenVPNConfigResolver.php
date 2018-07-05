<?php

namespace App\Libraries\Services;
use App\Node;
use App\Order;

class OpenVPNConfigResolver
{
    public static function resolveOrderConfig (Node $node, Order $order)
    {
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

        $certificate = \App\Libraries\PKIManager::issueUserChain($caData, $userIdentifier);

        return view('templates.openvpnuser', [
            'username' => $userIdentifier,
            'systemId' => $systemId,
            'listeners' => $parsedListeners,
            'certChunks' => chunk_split($certificate, 64, ""),
            'cipherType' => 'AES-256-CBC' // TODO: Allow this to be dynamically configurable someday.
        ])->render();
    }
}