<?php

namespace App\Http\Controllers\V1;
use App\Invoice;
use Illuminate\Http\Request;

class DebugController extends V1Controller
{
    public function test (Request $request)
    {
        $invoice = Invoice::find(3);
        $user = $request->user();
        dd($this->authorize('invoice.pdf', $invoice));
    }
}