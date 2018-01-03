<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public function cherryPick(Request $request, Array $validationRules)
    {
        $curatedData = [];
        foreach ($validationRules as $field => $rules)
        {
            if ($request->has($field))
                $curatedData[$field] = $request->get($field);
        }
        return $curatedData;
    }

}
