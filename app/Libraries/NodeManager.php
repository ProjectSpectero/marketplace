<?php


namespace App\Libraries;


use App\Node;
use GuzzleHttp\Client;

class NodeManager
{
    private $accessToken;
    private $baseUrl;

    private $jwtAccessToken;
    private $jwtRefreshToken;
    private $authenticated;

    private $client;
    private $headers;
    private $version;

    public function __construct (Node $node)
    {
        $this->baseUrl = $node->accessor();
        $this->accessToken = $node->access_token;

        $this->client = new Client([
            'base_url' => $this->baseUrl,
            'timeout' => env('NODE_REQUEST_TIMEOUT', 5)
        ]);

        $this->headers = [
            'User-Agent' => 'Spectero Verifier/v0.1~beta',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        $this->version = env('NODE_API_VERSION', 'v1');
    }

    public function authenticate ()
    {
        $localEndpoint = $this->getUrl('auth');
        $this->client->post($localEndpoint, [

        ]);
    }

    private function getUrl ($slug)
    {
        return '/' . $this->version . $slug;
    }
}