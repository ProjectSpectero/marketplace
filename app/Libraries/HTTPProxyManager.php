<?php

namespace App\Libraries;

use App\Constants\ResponseType;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class HTTPProxyManager
{
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => env('EXTERNAL_IP_RESOLVER', 'https://api.ipify.org/')
        ]);
    }

    public function verify (String $outgoingIP, String $proxyHost, String $proxyPort, String $proxyUsername, String $proxyPassword, int $attempts = 2)
    {
        $proxy = $this->formatProxy($proxyHost, $proxyPort, $proxyUsername, $proxyPassword);
        $connectTimeout = env('EXTERNAL_IP_RESOLUTION_TIMEOUT_SECONDS', 5);

        for ($i = 0; $i < $attempts; $i++)
        {
            try
            {
                $response = $this->client->get('/', [
                    'proxy' => $proxy,
                    'connect_timeout' => $connectTimeout
                ]);

                if ($response->getStatusCode() == ResponseType::OK)
                    return $response->getBody()->getContents() === $outgoingIP;
            }
            catch (RequestException $silenced)
            {
                // IP resolver gave something other than 200, who knows why?
                // That or the proxy didn't work, again not our headache.
                // Let us retry as long as attempts remain
            }
        }

        return false;
    }

    private function formatProxy (String $proxyHost, String $proxyPort, String $proxyUsername = '', String $proxyPassword = '', String $scheme = 'http') : String
    {
        return sprintf('%s://%s:%s@%s:%d', $scheme, $proxyUsername, $proxyPassword, $proxyHost, $proxyPort);
    }
}