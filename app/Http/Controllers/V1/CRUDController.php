<?php

namespace App\Http\Controllers\V1;
use App\Errors\FatalException;
use App\Errors\NotSupportedException;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as Request;

class CRUDController extends V1Controller
{
    protected $resource;

    public function index (Request $request) : JsonResponse
    {
        throw new NotSupportedException();
    }

    public function store (Request $request) : JsonResponse
    {
        throw new NotSupportedException();
    }

    public function update (Request $request, int $id) : JsonResponse
    {
        throw new NotSupportedException();
    }

    public function destroy (Request $request, int $id) : JsonResponse
    {
        throw new NotSupportedException();
    }

    public function show (Request $request, int $id) : JsonResponse
    {
        throw new NotSupportedException();
    }

    public function authorizeResource (Model $model = null, String $ability = null) : Response
    {
        $abilityName = $ability != null ? $ability : $this->resource . '.' . $this->getCallingMethodName();

        if ($model != null)
            return $this->authorize($abilityName, $model);

        return $this->authorize($abilityName);
    }

    private function getCallingMethodName (int $depth = 3) : String
    {
        $value = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $depth);
        $name = isset($value[2]['function']) ? $value[2]['function'] : null;
        if ($name == null)
            throw new FatalException("Could not autodetermine the calling function/method name, authorization is not possible.");

        return $name;
    }
}