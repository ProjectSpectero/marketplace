<?php

namespace App\Http\Controllers\V1;
use Illuminate\Http\Request;

class DebugController
{
    public function helloWorld (Request $request)
    {
        echo "Everything went better than expected!";
    }
}