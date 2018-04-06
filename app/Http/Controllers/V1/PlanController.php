<?php


namespace App\Http\Controllers\V1;


use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends V1Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->respond(config('plans', []));
    }

    public function show(Request $request, String $name, String $action = null): JsonResponse
    {
        $plans = config('plans');

        if (! isset($plans[$name]))
            throw new ModelNotFoundException();


        return $this->respond($plans[$name]);
    }
}