<?php

namespace App\Libraries;

use App\Constants\CRUDActions;
use App\Constants\ResponseType;
use Illuminate\Database\Eloquent\Model;
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
            $router->get($slug . '/{id}', self::generateRouteOptions($slug, $controller, CRUDActions::SHOW, $middlewares));

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
}