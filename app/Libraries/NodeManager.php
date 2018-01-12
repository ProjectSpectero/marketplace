<?php


namespace App\Libraries;

use App\Constants\Events;
use App\Events\NodeEvent;
use App\Node;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

class NodeManager
{
    private $node;
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
        $this->node = $node;
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
        $this->authenticate();
    }

    private function authenticate ()
    {
        $localEndpoint = $this->getUrl('auth');

        try
        {
            $results = $this->client->post($localEndpoint, [
                RequestOptions::JSON => $this->processAccessToken(),
                RequestOptions::HEADERS => $this->headers
            ])
            ->getBody()
            ->getContents();
        }
        catch (RequestException $exception)
        {
            $this->authenticated = false;
            $this->jwtAccessToken = null;
            $this->jwtRefreshToken = null;
            event(new NodeEvent(Events::NODE_VERIFICATION_FAILED, $this->node, [
                'errors' => $exception
            ]));
            return;
        }

        $returnedData = json_decode($results, true);

        if (empty($returnedData['errors']))
        {
            // No errors, everything went as expected.
            $this->authenticated = true;
            $this->jwtAccessToken = $returnedData['result'];
        }
        else
            event(new NodeEvent(Events::NODE_VERIFICATION_FAILED, $this->node, [
                'errors' => $returnedData['errors']
            ]));
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
        return $this->baseUrl . '/' . $this->version . '/' . $slug;
    }
}