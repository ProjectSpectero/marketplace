<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\OrderResourceType;
use App\Constants\ResponseType;
use App\Errors\UserFriendlyException;
use App\Node;
use App\NodeGroup;
use App\OrderLineItem;
use App\User;
use Illuminate\Database\Eloquent\Model;
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
        $user = $request->user();

        if (! $this->authorizeItem($user, $lineItem))
            throw new UserFriendlyException(Errors::UNAUTHORIZED, ResponseType::FORBIDDEN);

        return $this->respond($lineItem->toArray());
    }

    private function authorizeItem(User $user, Model $engagement)
    {
        $order = $engagement->order;
        $resourceType = $engagement->type;

        if ($resourceType == OrderResourceType::NODE)
            $resource = Node::findOrFail($engagement->resource);
        else
            $resource = NodeGroup::findOrFail($engagement->resource);

        if ($order->user_id == $user->id || $resource->user_id == $user->id)
            return true;

        return false;
    }

}
