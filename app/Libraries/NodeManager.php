<?php


namespace App\Libraries;

use App\Constants\Errors;
use App\Constants\Events;
use App\Constants\NodeConfigKey;
use App\Constants\NodeStatus;
use App\Constants\ResponseType;
use App\Constants\ServiceType;
use App\Errors\FatalException;
use App\Errors\UserFriendlyException;
use App\Events\NodeEvent;
use App\Node;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Validator;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha512;

class NodeManager
{
    private $implicitConnection;
    private $node;
    private $user;
    private $accessToken;
    private $baseUrl;

    private $jwtAccessToken;
    private $jwtRefreshToken;
    private $authResponse;

    private $authenticated;
    private $identity;

    private $client;
    private $headers;
    private $version;

    const UserDataClaim = 'http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata';


    public function __construct (Node $node, bool $implicitConnection = false, bool $useCommandProxy = false)
    {
        $this->implicitConnection = $implicitConnection;
        $this->node = $node;
        $this->baseUrl = $node->accessor();
        $this->accessToken = $node->access_token;
        $this->identity = $node->install_id;

        // TODO: This gives out the main IP of the cloud backend, which is a NO-NO (DDoS reasons). We need to abstract this out into discreet workers in prod.
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

    public function getTokens()
    {
        return $this->authResponse;
    }

    public static function generateAuthTokens (Node $node)
    {
        if ($node->status !== NodeStatus::CONFIRMED)
            throw new UserFriendlyException(Errors::NODE_PENDING_VERIFICATION);

        $expiryMinutes = env('NODE_JWT_TOKEN_EXPIRES_IN_MINUTES', 30);
        $signingKey = $node->getConfigKey(NodeConfigKey::CryptoJwtKey);

        $expires = Carbon::now()->addMinutes($expiryMinutes)->timestamp;

        list($user, $password) = explode(':', $node->access_token);

        // Need to build the right JWT payload to interact with the daemon-proxy now.
        $signer = new Sha512();
        $token = (new Builder())
            ->setIssuer(env('APP_URL', "https://cloud.spectero.com"))
            ->setId(uniqid("", true))
            ->setIssuedAt(time())
            ->setNotBefore(time())
            ->setExpiration($expires)
            ->set(self::UserDataClaim, [
                'id' => 1, // Only one really guaranteed to exist, maybe we shouldn't include it at all.
                'source' => 'SpecteroCloud',
                'authKey' => $user,
                'roles' => [ 'SuperAdmin' ],
                'cert' => null,
                'certKey' => null,
                'encryptCertificate' => true,
                'engagementId' => 0,
                'fullName' => 'Spectero Cloud',
                'emailAddress' => 'cloud@spectero.com',
                'cloudSyncDate' => Carbon::now()->toDateTimeString(),
            ])
            ->sign($signer, $signingKey)
            ->getToken();

        return [
            'access' => [ 'token' => (string) $token, 'expires' => $expires ],
            'refresh' => [ 'token' => null, 'expires' => $expires ],
        ];
    }

    public function getAndValidateSystemDescriptor ()
    {
        $rules = [
            'systemConfig' => 'required|array',
            'appSettings' => 'required|array',
            'status' => 'required|array',
            'status.cloud' => 'required|array',
            'status.app' => 'required|array',
            'status.system' => 'required|array',
            'status.app.environment' => 'required|equals:Production',
            'status.app.restartNeeded' => 'required|equals:false',
            'appSettings.BlockedRedirectUri' => 'required|equals:https://blocked.spectero.com/?reason={0}&uri={1}&data={2}',
            'appSettings.AuthCacheMinutes' => 'required|integer|max:10',
            'appSettings.LocalSubnetBanEnabled' => 'required|equals:true',
            'appSettings.JWTTokenExpiryInMinutes' => 'required|integer|max:100|min:5',
            'appSettings.JWTRefreshTokenDelta' => 'required|integer|min:30|max:100',
            'appSettings.RespectEndpointToOutgoingMapping' => 'required|equals:true',
            'appSettings.InMemoryAuth' => 'required|equals:true',
            'appSettings.InMemoryAuthCacheMinutes' => 'required|integer|max:5',
            'appSettings.AutoStartServices' => 'required|equals:true',
            'identity' => 'required|alpha_dash'
        ];

        $response = $this->request('get', $this->getUrl('cloud/descriptor'));
        $result = $response['result'];

        /** @var \Illuminate\Validation\Validator $validator */
        $validator = Validator::make($result, $rules);

        if ($validator->fails())
            throw new FatalException($validator->errors()->toJson());

        if($this->identity != $result['identity'])
            throw new FatalException('Identity mismatch: expected ' . $this->identity . ', but got ' . $result['identity']);

        return $result;
    }

    public function discover (bool $loadServiceConfigs = false, bool $throwException = false)
    {
        $ret = [];

        try
        {
            if (! $this->implicitConnection)
            {
                $this->authenticate();
                $this->validateAccessLevel();
            }

            $convergedDescriptor = $this->getAndValidateSystemDescriptor();

            $ret['appSettings'] = $convergedDescriptor['appSettings'];
            $ret['systemConfig'] = $convergedDescriptor['systemConfig'];
            $ret['systemData'] = $convergedDescriptor['status']['system'];

            $ret['ipAddresses'] = $this->discoverIPAddresses();

            $services = $this->discoverServices()['result'];
            foreach ($services as $service => $state)
            {
                $this->validateServiceName($service);

                $config = $loadServiceConfigs ? $this->getServiceConfig($service) : null;

                // TODO: Make this capable of getting resources from all services
                $connectionResource = $service == 'HTTPProxy' ? $this->getServiceConnectionResources($service) : null;

                $ret['services'][$service] = [
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

            if ($throwException)
                throw new FatalException(Events::NODE_VERIFICATION_FAILED, ResponseType::INTERNAL_SERVER_ERROR, $exception);

            return null;
        }

        return $ret;
    }

    public function heartbeat ()
    {
        return $this->request('get', $this->getUrl('cloud/heartbeat'), [], true);
    }

    public function discoverIdentity ()
    {
        return $this->request('get', $this->getUrl('cloud/identity'), [], true);
    }

    public function discoverServices ()
    {
        $localEndpoint = $this->getUrl('service');

        return $this->request('get', $localEndpoint, []);
    }

    public function discoverIPAddresses ()
    {
        $localEndpoint = $this->getUrl('service/ips');

        return $this->request('get', $localEndpoint, [], true);
    }

    public function getServiceConfig (String $serviceName)
    {
        $this->validateServiceName($serviceName);

        $localEndpoint = $this->getUrl('service/' . $serviceName . '/config');

        return $this->request('get', $localEndpoint, [], true);
    }

    public function getServiceConnectionResources (String $serviceName = '')
    {
        if (! empty($serviceName))
            $this->validateServiceName($serviceName);

        $slug = 'user/' . $this->user['id'] . '/service-resources';
        if (! empty($serviceName))
            $slug .= '/' . $serviceName;

        $localEndpoint = $this->getUrl($slug);

        return $this->request('get', $localEndpoint, [], true);
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

        // No errors, everything likely went as expected.
        $returnedData = $returnedData['result'];
        $this->authResponse = $returnedData;

        $this->jwtAccessToken = isset($returnedData['access']['token']) ? $returnedData['access']['token'] : null;
        $this->jwtRefreshToken = isset($returnedData['refresh']['token']) ? $returnedData['refresh']['token'] : null;

        if ($this->jwtAccessToken != null)
            $this->authenticated = true;
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

    private function request($method, $localEndpoint, $json = [], $resolveResults = false)
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

        return $resolveResults ? $returnedData['result'] : $returnedData;
    }
}