<?php

namespace App\Http\Controllers\V1;

use App\Errors\NotSupportedException;
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
}