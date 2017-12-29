<?php


namespace App\Libraries;


use Illuminate\Http\JsonResponse;

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
}