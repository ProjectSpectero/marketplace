<?php

namespace App\Http\Controllers\V1;

use App\Constants\CRUDActions;
use App\Constants\ResponseType;
use App\Constants\UserRoles;
use App\Errors\FatalException;
use App\Http\Controllers\Controller;
use App\Libraries\Utility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class V1Controller extends Controller
{
    public $version = 'v1';

    protected $resource;

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

    public function respond (Array $data = null, Array $errors = [],
                             String $message = null, int $statusCode = ResponseType::OK,
                             Array $headers = [], Array $paginationParams = []) : JsonResponse
    {
        return Utility::generateResponse($data, $errors, $message, $this->version, $statusCode, $headers, $paginationParams);
    }

    public function authorizeResource (Model $model = null, String $ability = null)
    {
        $abilityName = $ability != null ? $ability : $this->resource . '.' . $this->getCallingMethodName();

        if (\Auth::user()->isAn(UserRoles::ADMIN))
            return true;

        if ($model != null)
            return $this->authorize($abilityName, $model);

        return $this->authorize($abilityName);
    }

    private function getCallingMethodName (int $depth = 3) : String
    {
        $value = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $depth);
        $name = isset($value[2]['function']) ? $value[2]['function'] : null;
        if ($name == null || ! in_array($name, CRUDActions::getConstants()))
            throw new FatalException("Could not autodetermine the calling function/method name, authorization is not possible.");

        return $name;
    }

}
