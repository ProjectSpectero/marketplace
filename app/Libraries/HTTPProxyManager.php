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

    public function verfiy (String $outgoingIP, String $proxyHost, String $proxyPort, String $proxyUsername, String $proxyPassword)
    {
        try
        {
            $proxy = $this->formatProxy($proxyHost, $proxyPort, $proxyUsername, $proxyPassword);
            $response = $this->client->get('/', [ 'proxy' => $proxy ]);
            if ($response->getStatusCode() == ResponseType::OK)
            {
                return $response->getBody()->getContents() === $outgoingIP;
            }
        }
        catch (RequestException $exception)
        {
            // IP resolver gave something other than 200, who knows why?
            return false;
        }

        return false;
    }

    private function formatProxy (String $proxyHost, String $proxyPort, String $proxyUsername, String $proxyPassword) : String
    {
        return sprintf('http://%s:%s@%s:%d', $proxyHost, $proxyPort);
    }
}