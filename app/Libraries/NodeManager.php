<?php


namespace App\Libraries;


use App\Node;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

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

        try
        {
            $results = $this->client->post($this->baseUrl. '/' .$localEndpoint, [
                RequestOptions::JSON => $this->processAccessToken(),
                RequestOptions::HEADERS => $this->headers
            ]);
        }
        catch (RequestException $exception)
        {
            dd($exception);
        }

        dd($results);
    }

    private function processAccessToken ()
    {
        list($authKey, $password) = explode(':', $this->accessToken);

        return [
            'authKey' => $authKey,
            'password' => $password
        ];
    }
    private function getUrl ($slug)
    {
        return $this->version . '/' . $slug;
    }
}