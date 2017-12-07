<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiController extends Controller
{
    /**
     * Method to use the spectero standard unified response style when returning
     * a response
     *
     * @param array $errors
     * @param array $data
     * @param string $message
     *
     * @return JSON
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
}
