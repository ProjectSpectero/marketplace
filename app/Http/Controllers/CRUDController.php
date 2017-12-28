<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request as Request;

abstract class CRUDController extends V1Controller
{
    abstract function doCreate (Request $request);
    abstract function doEdit (Request $request);
    abstract function doDelete (Request $request);
    abstract function viewOne (int $id);
    abstract function viewAll ();
}