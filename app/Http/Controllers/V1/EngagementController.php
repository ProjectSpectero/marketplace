<?php

namespace App\Http\Controllers\V1;

use App\OrderLineItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EngagementController extends CRUDController
{

    public function __construct()
    {
        $this->resource = 'engagement';
    }

    public function show(Request $request, int $id, String $action = null): JsonResponse
    {
        $lineItem = OrderLineItem::findOrFail($id);
        $this->authorizeResource($lineItem);

        return $this->respond($lineItem->toArray());
    }

}
