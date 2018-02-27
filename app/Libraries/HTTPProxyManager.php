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

    public function discover (String $proxyHost, String $proxyPort, String $proxyUsername, String $proxyPassword, int $attempts = 2)
    {
        $proxy = $this->formatProxy($proxyHost, $proxyPort, $proxyUsername, $proxyPassword);
        $connectTimeout = env('EXTERNAL_IP_RESOLUTION_TIMEOUT_SECONDS', 5);

        for ($i = 0; $i < $attempts; $i++)
        {
            $response = $this->client->get('/', [
                'proxy'           => $proxy,
                'connect_timeout' => $connectTimeout
            ]);

            if ($response->getStatusCode() == ResponseType::OK)
                return $response->getBody()->getContents();
        }

        return false;
    }

    private function formatProxy (String $proxyHost, String $proxyPort, String $proxyUsername = '', String $proxyPassword = '', String $scheme = 'http') : String
    {
        return sprintf('%s://%s:%s@%s:%d', $scheme, $proxyUsername, $proxyPassword, $proxyHost, $proxyPort);
    }
}