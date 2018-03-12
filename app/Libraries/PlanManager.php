<?php


namespace App\Libraries;


use App\Constants\OrderResourceType;
use App\Constants\SubscriptionPlan;
use App\Order;
use App\OrderLineItem;
use App\User;

class PlanManager
{
    private static $plans;
    private static function loadMappings ()
    {
        $pro = env('PRO_RESOURCE', "");
        static::$plans = [
            'pro' => []
        ];

        $plans = static::$plans;

        if (! empty($pro))
        {
            $elements = explode(',', $pro);
            foreach ($elements as $element)
            {
                list($type, $id) = explode('/', $element);
                $plans['pro'][$type] = [];
                $plans['pro'][$type][$id] = true; // Why this trainwreck instead of normal elements? Because it has constant time lookups.
            }
        }

        return $plans;
    }

    // For $plan, use the 'SubscriptionPlan' constant
    public static function isMember (User $user, String $plan, bool $throwsExceptions = false) : bool
    {
        $plans = self::loadMappings();

        switch ($plan)
        {
            case SubscriptionPlan::PRO:
                foreach ($plans as $membershipPlan)
                {
                    $hasProPlan = OrderLineItem::findForUser($user->id)
                        ->where('type', $membershipPlan['key'])
                        ->get();

                    if ($hasProPlan)
                        return true;
                }
                break;
            default:
                return false;
        }

    }

}