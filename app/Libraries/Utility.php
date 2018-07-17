<?php

namespace App\Libraries;

use App\Constants\CRUDActions;
use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Constants\UserMetaKeys;
use App\Constants\UserStatus;
use App\User;
use App\UserMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Routing\Router;

class Utility
{

    public static $metaDataTypes = ['boolean', 'integer', 'double', 'float', 'string'];

    public static function getRandomString (int $count = 1) : String
    {
        $ret = "";
        for ($i = 0; $i < $count; $i++)
        {
            $ret .= md5(uniqid(mt_rand(), true));
        }

        return $ret;
    }

    public static function generateResponse (Array $data = null, Array $errors = [],
                                             String $message = null, String $version = 'v1',
                                             int $statusCode = ResponseType::OK, Array $headers = [],
                                             Array $paginationParams = []) : JsonResponse
    {
        $response = [
            'errors' => $errors,
            'result' => $data,
            'message' => $message,
            'version' => $version
        ];

        if (! empty($paginationParams))
            $response['pagination'] = $paginationParams;

        return response()
            ->json($response, $statusCode, $headers);
    }

    public static function defineResourceRoute (String $slug, String $controller, Router $router, Array $middlewares, Array $rules = []) : void
    {
        if (! self::checkIfActionIsExcluded($rules, CRUDActions::INDEX))
            $router->get($slug, self::generateRouteOptions($slug, $controller, CRUDActions::INDEX, $middlewares));

        if (! self::checkIfActionIsExcluded($rules, CRUDActions::SHOW))
            $router->get($slug . '/{id}[/{action}]', self::generateRouteOptions($slug, $controller, CRUDActions::SHOW, $middlewares));

        if (! self::checkIfActionIsExcluded($rules, CRUDActions::STORE))
            $router->post($slug, self::generateRouteOptions($slug, $controller, CRUDActions::STORE, $middlewares));

        if (! self::checkIfActionIsExcluded($rules, CRUDActions::UPDATE))
            $router->put($slug . '/{id}', self::generateRouteOptions($slug, $controller, CRUDActions::UPDATE, $middlewares));

        if (! self::checkIfActionIsExcluded($rules, CRUDActions::DESTROY))
            $router->delete($slug . '/{id}', self::generateRouteOptions($slug, $controller, CRUDActions::DESTROY, $middlewares));
    }

    private static function generateRouteOptions (String $slug, String $controller, String $action, Array $middlewares) : array
    {
        $options = [
            'uses' => $controller . '@' . $action,
            'as' => $slug . '_' . $action
        ];

        if (count($middlewares) != 0)
        {
            $options['middleware'] = $middlewares;
        }

        return $options;
    }

    private static function checkIfActionIsExcluded (Array $rules, String $action) : bool
    {
        if (isset($rules['excluded']))
            return is_array($rules['excluded']) ? in_array($action, $rules['excluded']) : $action == $rules['excluded'];

        return false;
    }

    public static function getModelFromResourceSlug (String $slug) : Model
    {
        $baseModelNamespace = 'App\\';
        $modelName = $baseModelNamespace . studly_case($slug);
        return new $modelName;
    }

    public static function getPreviousModel (Array $dataBag)
    {
        return isset($dataBag['previous']) ? $dataBag['previous'] : null;
    }

    public static function getError (Array $databag)
    {
        return isset($databag['error']) ? $databag['error'] : null;
    }

    public static function generateUrl (String $path, String $deployment = 'backend') : string
    {
        $base = env('APP_URL') . '/';
        $base .= $deployment == 'backend' ? env('API_VERSION') : '';
        $base .= $deployment == 'backend' ? '/' . $path : $path;

        return $base;
    }

    public static function resolveStatusError (User $user) : string
    {
        switch ($user->status)
        {
            case UserStatus::EMAIL_VERIFICATION_NEEDED:
                return Errors::EMAIL_VERIFICATION_NEEDED;
            case UserStatus::ACTIVE:
                return "";
            default:
                // Yeah -_-
                return Errors::AUTHENTICATION_NOT_ALLOWED;
                break;
        }
    }

    public static function alphaDashRule(String $str)
    {
        return ( ! preg_match("/^([-a-z])+$/i", $str)) ? FALSE : TRUE;
    }

    public static function joinPaths (string ...$parts): string
    {
        $parts = array_map('trim', $parts);
        $path = [];

        foreach ($parts as $part) {
            if ($part !== '') {
                $path[] = $part;
            }
        }

        $path = implode(DIRECTORY_SEPARATOR, $path);

        return preg_replace(
            '#' . preg_quote(DIRECTORY_SEPARATOR) . '{2,}#',
            DIRECTORY_SEPARATOR,
            $path
        );
    }

    public static function incrementLoginCount (User $user)
    {
        $value = 0;
        try
        {
            $value = UserMeta::loadMeta($user, UserMetaKeys::LoginCount, true)->meta_value;
        }
        catch (ModelNotFoundException $silenced)
        {

        }

        UserMeta::addOrUpdateMeta($user, UserMetaKeys::LoginCount, $value + 1);
    }

}