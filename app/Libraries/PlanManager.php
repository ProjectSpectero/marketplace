<?php


namespace App\Libraries;


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
    }

    // For $plan, use the 'SubscriptionPlan' constant
    private static function isMember (User $user, String $plan, bool $throwsExceptions = false) : bool
    {
        //TODO: build this
    }


}