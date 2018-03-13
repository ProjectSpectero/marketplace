<?php

return [
    \App\Constants\SubscriptionPlan::PRO => [
        'resources' => [
            [ 'type' => \App\Constants\OrderResourceType::NODE_GROUP, 'id' => env('PRO_PLAN_GROUP_ID') ]
        ],
        'yearly_discount_pct' => env('PRO_PLAN_YEARLY_DISCOUNT_PCT', 50),
        'node_limit' => env('PRO_PLAN_NODE_LIMIT', 150)
    ]
];