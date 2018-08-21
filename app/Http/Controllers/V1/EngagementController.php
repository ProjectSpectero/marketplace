<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\Events;
use App\Constants\OrderResourceType;
use App\Constants\OrderStatus;
use App\Constants\ResponseType;
use App\Errors\FatalException;
use App\Errors\UserFriendlyException;
use App\Events\BillingEvent;
use App\Libraries\BillingUtils;
use App\Node;
use App\NodeGroup;
use App\OrderLineItem;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EngagementController extends CRUDController
{
    public function show(Request $request, int $id, String $action = null): JsonResponse
    {
        $lineItem = OrderLineItem::findOrFail($id);
        $this->authorizeItem($lineItem);


        return $this->respond($lineItem->toArray());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $lineItem = OrderLineItem::findOrFail($id);
        $this->authorizeItem($lineItem, 'destroy');

        // We'll never actually remove the entry, simply mark is as cancelled (which will prevent it from being billed + being pushed to nodes).
        // The name is thus quite misleading, indeed.

        if ($lineItem->status != OrderStatus::ACTIVE)
            throw new UserFriendlyException(Errors::ORDER_NOT_ACTIVE_YET);

        // TODO: Build support for full ent handling, and at that point enable cancellations.
        if ($lineItem->type == OrderResourceType::ENTERPRISE)
            throw new UserFriendlyException(Errors::CONTACT_ACCOUNT_REPRESENTATIVE, ResponseType::FORBIDDEN);

        $lineItem->status = OrderStatus::CANCELLED;
        $lineItem->saveOrFail();

        event(new BillingEvent(Events::ORDER_REVERIFY, $lineItem->order));

        return $this->respond($lineItem->toArray());
    }

    private function authorizeItem(OrderLineItem $engagement, String $caller = 'show')
    {
        $user = \Auth::user();
        if ($user == null)
            throw new FatalException("EngagementController#authorizeItem: User was null, this is not supposed to happen.");

        switch ($caller)
        {
            case 'show':
            case 'destroy':
                $order = $engagement->order;
                $resourceType = $engagement->type;

                if ($resourceType == OrderResourceType::NODE)
                    $resource = Node::findOrFail($engagement->resource);
                else
                    $resource = NodeGroup::findOrFail($engagement->resource);

                if ($order->user_id == $user->id || $resource->user_id == $user->id)
                    return true;

                break;
        }

        throw new UserFriendlyException(Errors::UNAUTHORIZED, ResponseType::FORBIDDEN);
    }


}
