<?php


namespace App\Libraries;


use App\Constants\Errors;
use App\Constants\OrderResourceType;
use App\Constants\OrderStatus;
use App\Constants\ResponseType;
use App\Constants\SubscriptionPlan;
use App\Errors\UserFriendlyException;
use App\Order;
use App\OrderLineItem;
use App\User;

class PlanManager
{
    private static function loadMappings ()
    {
        $pro = env('PRO_RESOURCE', "");
        $plans = [
            SubscriptionPlan::PRO => []
        ];

        if (! empty($pro))
        {
            $elements = explode(',', $pro);
            foreach ($elements as $element)
            {
                list($type, $id) = explode('/', $element);
                $plans[SubscriptionPlan::PRO][$type] = [];
                $plans[SubscriptionPlan::PRO][$type][$id] = true; // Why this trainwreck instead of normal elements? Because it has constant time lookups.
            }
        }

        return $plans;
    }

    public static function isMember (User $user, String $plan, bool $throwsExceptions = false) : bool
    {
        $plans = static::loadMappings();

        if(! isset($plans[$plan]))
            throw new UserFriendlyException(Errors::NO_SUCH_SUBSCRIPTION_PLAN);

        foreach ($plans[$plan] as $resource => $id)
        {
            $query = OrderLineItem::join('orders', 'order_line_items.order_id', '=', 'orders.id')
                ->where('orders.status', OrderStatus::ACTIVE)
                ->where('orders.user_id', $user->id)
                ->where('order_line_items.type', strtoupper($resource))
                ->where('order_line_items.resource', $id);

            if ($query->count() != 0)
                return true;
        }

        if ($throwsExceptions)
            throw new UserFriendlyException(Errors::USER_NOT_SUBSCRIBED . ':' . $plan, ResponseType::NOT_AUTHORIZED);

        return false;
    }

}