<?php


namespace App\Libraries;


use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Routing\Router;
use PhpParser\Node\Expr\Array_;

class Utility
{
    public static function getRandomString () : String
    {
        return md5(uniqid(mt_rand(), true));
    }

    public static function generateResponse (Array $data = null, Array $errors = [], String $message = null, String $version = "v1", int $statusCode = 200, Array $headers = []) : JsonResponse
    {
        return response()
            ->json([
                'errors' => $errors,
                'result' => $data,
                'message' => $message,
                'version' => $version
            ], $statusCode, $headers);
    }

    public static function defineResourceRoute (String $slug, String $controller, Router $router, Array $middlewares) : void
    {
        $router->get($slug, self::generateRouteOptions($controller, "index", $middlewares));
        $router->get($slug . "/{id}", self::generateRouteOptions($controller, "show", $middlewares));
        $router->post($slug, self::generateRouteOptions($controller, "store", $middlewares));
        $router->put($slug . "/{id}", self::generateRouteOptions($controller, "update", $middlewares));
        $router->delete($slug . "/{id}", self::generateRouteOptions($controller, "destroy", $middlewares));
    }

    private static function generateRouteOptions (String $controller, String $action, Array $middlewares) : array
    {
        $options = [ "uses" => $controller . "@" . $action ];
        if (count($middlewares) > 1)
        {
            $options["middleware"] = $middlewares;
        }

        return $options;
    }
}