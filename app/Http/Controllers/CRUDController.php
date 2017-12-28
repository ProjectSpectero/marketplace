<?php

namespace App\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as Request;

abstract class CRUDController extends V1Controller
{
    abstract function doCreate (Request $request) : JsonResponse;
    abstract function doEdit (Request $request, int $id) : JsonResponse;
    abstract function doDelete (int $id) : JsonResponse;
    abstract function viewOne (int $id) : JsonResponse;
    abstract function viewAll () : JsonResponse;
}