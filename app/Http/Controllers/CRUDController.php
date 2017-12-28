<?php

namespace App\Http\Controllers;
use Illuminate\Database\Eloquent\Model as Model;

abstract class CRUDController extends V1Controller
{
    abstract function doCreate ($model);
    abstract function doEdit (Model $model);
    abstract function doDelete (Model $model);
}