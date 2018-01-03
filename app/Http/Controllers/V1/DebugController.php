<?php

namespace App\Http\Controllers\V1;
use Illuminate\Http\Request;

class DebugController
{
    public function multiFactorTest (Request $request)
    {
        echo "Everything went better than expected!";
    }
}