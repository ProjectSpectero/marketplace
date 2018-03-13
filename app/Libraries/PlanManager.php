<?php
namespace App\Libraries;

use App\Constants\Errors;
use App\Constants\OrderStatus;
use App\Constants\ResponseType;
use App\Errors\UserFriendlyException;
use App\OrderLineItem;
use App\User;

class PlanManager
{
    public static function resolveMemberships (User $user) : array
    {
        $plans = config('plans', []);
        $ret = [];

        foreach ($plans as $name => $plan)
        {
            $resources = $plans[$name]['resources'];
            foreach ($resources as $resource)
            {
                $query = OrderLineItem::join('orders', 'order_line_items.order_id', '=', 'orders.id')
                    ->where('orders.status', OrderStatus::ACTIVE)
                    ->where('orders.user_id', $user->id)
                    ->where('order_line_items.type', $resource['type'])
                    ->where('order_line_items.resource', $resource['id']);

                if ($query->count() != 0)
                    $ret[$name] = $plan;
            }
        }

        return $ret;
    }
    public static function isMember (User $user, String $plan, bool $throwsExceptions = false) : array
    {
        $plans = config('plans', []);

        if(! isset($plans[$plan]))
            throw new UserFriendlyException(Errors::NO_SUCH_SUBSCRIPTION_PLAN);

        $membership = static::resolveMemberships($user, $throwsExceptions);

        if (isset($membership[$plan]))
            return $membership[$plan];

        if ($throwsExceptions)
            throw new UserFriendlyException(Errors::USER_NOT_SUBSCRIBED . ':' . $plan, ResponseType::NOT_AUTHORIZED);

        return false;
    }
}