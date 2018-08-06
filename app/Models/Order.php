<?php

namespace App;

use App\Constants\OrderResourceType;
use App\Constants\OrderStatus;

class Order extends BaseModel
{
    protected $with = [ 'lineItems', 'lastInvoice' ];
    protected $hidden = [ 'notes', 'user_id', 'subscription_reference', 'subscription_provider', 'accessor' ];
    protected $dates = [
        'created_at',
        'updated_at',
        'due_next'
    ];

    public $searchAble = ['due_next', 'status', 'term', 'created_at', 'id'];

    public function lineItems()
    {
        return $this->hasMany(OrderLineItem::class);
    }

    public function lastInvoice()
    {
        return $this->hasOne(Invoice::class, 'id', 'last_invoice_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isOfType (string $orderResourceType) : bool
    {
        foreach ($this->lineItems as $lineItem)
        {
            if ($lineItem->status == OrderStatus::ACTIVE
                && $lineItem->type == $orderResourceType)
                return true;
        }

        return false;
    }

    public function isEnterprise () : bool
    {
        return $this->isOfType(OrderResourceType::ENTERPRISE);
    }

    public function plans () : array
    {
        $plans = [];
        foreach ($this->lineItems as $lineItem)
        {
            $resource = null;
            switch ($lineItem->type)
            {
                case OrderResourceType::NODE:
                    $resource = Node::find($lineItem->resource);

                    break;

                case OrderResourceType::NODE_GROUP:
                    $resource = NodeGroup::find($lineItem->resource);

                    break;

                case OrderResourceType::ENTERPRISE:
                    if (! in_array(strtolower(OrderResourceType::ENTERPRISE), $plans))
                        $plans[] = strtolower(OrderResourceType::ENTERPRISE);

                    break;
            }

            if ($resource != null && isset(config('plans')[$resource->plan])
                && ! in_array($resource->plan, $plans))
            {
                $plans[] = $resource->plan;
            }
        }

        return $plans;
    }

    public function canBypassBillingProfileCheck () : bool
    {
        $plans = config('plans');

        foreach ($this->plans() as $plan)
        {
            // Yep, we redefined the variable (since the original is useless).
            $plan = $plans[$plan] ?? null;

            if (isset($plan['easy_allowed']) && $plan['easy_allowed'] == true)
                return true;
        }

        return false;
    }
}
