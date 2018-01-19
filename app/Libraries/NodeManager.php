<?php


namespace App\Libraries;

use App\Constants\Errors;
use App\Constants\Events;
use App\Constants\ResponseType;
use App\Constants\ServiceType;
use App\Errors\FatalException;
use App\Events\NodeEvent;
use App\Node;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

class NodeManager
{
    private $implicitConnection;
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


    public function __construct (Node $node, bool $implicitConnection = false)
    {
        $this->implicitConnection = $implicitConnection;
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

        if ($implicitConnection)
        {
            $this->authenticate();
            $this->validateAccessLevel();
        }
    }

    public function firstTimeDiscovery ()
    {
        $ret = [];

        try
        {
            if (! $this->implicitConnection)
            {
                $this->authenticate();
                $this->validateAccessLevel();
            }

            $services = $this->discoverServices()['result'];
            foreach ($services as $service => $state)
            {
                $this->validateServiceName($service);
                $config = $this->getServiceConfig($service);
                $connectionResource = $this->getServiceConnectionResources($service);

                $ret[$service] = [
                    'config' => $config,
                    'connectionResource' => $connectionResource
                ];
            }
        }
        catch (RequestException | FatalException $exception)
        {
            event(new NodeEvent(Events::NODE_VERIFICATION_FAILED, $this->node, [
                'error' => $exception->getMessage()
            ]));
            return null;
        }

        return $ret;
    }

    public function discoverServices ()
    {
        $localEndpoint = $this->getUrl('service');

        return $this->request('get', $localEndpoint);
    }

    public function discoverIPAddresses ()
    {
        $localEndpoint = $this->getUrl('service/ips');

        return $this->request('get', $localEndpoint);
    }

    public function getServiceConfig (String $serviceName)
    {
        $this->validateServiceName($serviceName);

        $localEndpoint = $this->getUrl('service/' . $serviceName . '/config');

        return $this->request('get', $localEndpoint);
    }

    public function getServiceConnectionResources (String $serviceName = '')
    {
        if (! empty($serviceName))
            $this->validateServiceName($serviceName);

        $slug = 'user/' . $this->user['id'] . '/service-resources';
        if (! empty($serviceName))
            $slug .= '/' . $serviceName;

        $localEndpoint = $this->getUrl($slug);

        return $this->request('get', $localEndpoint);
    }

    public function manageService (String $serviceName, String $actionName)
    {
        $this->validateServiceName($serviceName);
        $this->validateServiceAction($actionName);

        $localEndpoint = $this->getUrl('service/' . $serviceName . '/' . $actionName);

        return $this->request('get', $localEndpoint);
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
        if (! $this->authenticated)
            throw new FatalException(Errors::COULD_NOT_ACCESS_NODE);

        $localEndpoint = $this->getUrl('user/self');

        $response = $this->request('get', $localEndpoint);
        $this->user = $response['result'];

        foreach ($this->user['roles'] as $role)
        {
            if (! in_array($role, ['SuperAdmin', 'WebApi']))
            {
                throw new FatalException(Errors::ACCESS_LEVEL_INSUFFICIENT);
            }
        }
    }

    private function authenticate ()
    {
        $localEndpoint = $this->getUrl('auth');
        $returnedData = $this->request('post', $localEndpoint, $this->processAccessToken());

        // No errors, everything went as expected.
        $this->authenticated = true;
        $this->jwtAccessToken = $returnedData['result'];
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

    private function request($method, $localEndpoint, $json = [])
    {
        if ($this->authenticated)
            $this->headers['Authorization'] = 'Bearer ' . $this->jwtAccessToken;

        try
        {
            $results = $this->client->request($method, $localEndpoint, [
                RequestOptions::JSON => $json,
                RequestOptions::HEADERS => $this->headers
            ])
                ->getBody()
                ->getContents();
        }
        catch (RequestException $exception)
        {
            $response = $exception->getResponse();
            if ($response != null && $response->getStatusCode() == ResponseType::NOT_AUTHORIZED)
            {
                $this->authenticated = false;
                $this->jwtAccessToken = null;

                if (isset($this->headers['Authorization']))
                    unset($this->headers['Authorization']);
            }
            throw $exception;
        }

        $returnedData = json_decode($results, true);
        return $returnedData;
    }
}