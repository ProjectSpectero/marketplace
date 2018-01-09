<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public function cherryPick(Request $request, Array $validationRules)
    {
        return $request->only(array_keys($validationRules));
    }
}
