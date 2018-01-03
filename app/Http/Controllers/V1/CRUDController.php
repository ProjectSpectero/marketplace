<?php

namespace App\Http\Controllers\V1;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as Request;

abstract class CRUDController extends V1Controller
{
    abstract function store(Request $request) : JsonResponse;
    abstract function update(Request $request, int $id) : JsonResponse;
    abstract function destroy(int $id) : JsonResponse;
    abstract function show(int $id) : JsonResponse;
    abstract function index() : JsonResponse;
}