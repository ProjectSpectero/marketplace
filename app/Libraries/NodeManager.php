<?php


namespace App\Libraries;

use App\Constants\Errors;
use App\Constants\Events;
use App\Constants\ServiceType;
use App\Errors\FatalException;
use App\Events\NodeEvent;
use App\Node;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

class NodeManager
{
    private $node;
    private $user;
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
        $this->validateAccessLevel();
    }

    public function discoverServices ()
    {
        // TODO: implement this, needs bearer token auth
        $localEndpoint = $this->getUrl('service');
    }

    public function discoverIPAddresses ()
    {
        // TODO: implement this, needs bearer token auth
        $localEndpoint = $this->getUrl('service/ips');
    }

    public function getServiceConfig (String $serviceName)
    {
        $this->validateServiceName($serviceName);

        // TODO: implement this, needs bearer token auth
        $localEndpoint = $this->getUrl('service/' . $serviceName . '/config');
    }

    public function getServiceConnectionResources (String $serviceName = '')
    {
        if (! empty($serviceName))
            $this->validateServiceName($serviceName);

        // TODO: implement this, needs bearer token auth
        $slug = 'user/' . $this->user['id'] . '/service-resources';
        if (! empty($serviceName))
            $slug .= '/' . $serviceName;

        $localEndpoint = $this->getUrl($slug);
    }

    public function manageService (String $serviceName, String $actionName)
    {
        $this->validateServiceName($serviceName);
        $this->validateServiceAction($actionName);

        // TODO: implement this, needs bearer token auth
        $localEndpoint = $this->getUrl('service/' . $serviceName . '/' . $actionName);
    }

    private function validateServiceAction (String $actionName)
    {
        if (! in_array($actionName, [ 'start', 'stop', 'restart', 'config' ]))
            throw new FatalException(Errors::UNKNOWN_ACTION);
    }

    private function validateServiceName (String $serviceName)
    {
        if (! in_array($serviceName, ServiceType::getConstants()))
            throw new FatalException(Errors::UNKNOWN_SERVICE);
    }

    private function validateAccessLevel ()
    {
        // TODO: Decode $this->jwtAccessToken, and store the user into $this->>user
        // Then check that the user's roles array has either 'SuperAdmin' or 'WebApi'
        // See https://puu.sh/yZyLv/a25abfde3b.png for the schema
        // If it doesn't, throw new FatalException(Errors::ACCESS_LEVEL_INSUFFICIENT);
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

        if (! $this->authenticated)
            throw new FatalException(Errors::COULD_NOT_ACCESS_NODE);
    }

    private function processAccessToken ()
    {
        list($authKey, $password) = explode(':', $this->accessToken, 2);

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