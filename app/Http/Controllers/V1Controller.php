<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class V1Controller extends Controller
{
    public $version = "v1";

    /**
     * Method to use the spectero standard unified response style when returning
     * a response
     *
     * @param array $errors
     * @param array $data
     * @param string $message
     *
     * @return JsonResponse
     */


    public function unifiedResponse($errors, $data, $message)
    {
        return response()->json([
            'errors' => $errors,
            'result' => $data,
            'message' => $message,
            'version' => env('API_VERSION')
        ]);
    }

    /**
     * Method to use the spectero standard unified response style when returning
     * a response
     *
     * @param array $errors
     * @param array $data
     * @param string $message
     * @param int $statusCode
     * @param array $headers
     *
     * @return JsonResponse
     */

    public function respond (Array $data = null, Array $errors = [], String $message = null, int $statusCode = 200, Array $headers = []) : JsonResponse
    {
        return response()
            ->json([
                'errors' => $errors,
                'result' => $data,
                'message' => $message,
                'version' => $this->version
            ], $statusCode, $headers);
    }

}
