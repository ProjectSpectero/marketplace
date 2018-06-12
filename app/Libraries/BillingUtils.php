<?php


namespace App\Libraries;


use App\Constants\Currency;
use App\Constants\Errors;
use App\Constants\InvoiceStatus;
use App\Constants\InvoiceType;
use App\Constants\NodeMarketModel;
use App\Constants\OrderResourceType;
use App\Constants\OrderStatus;
use App\Constants\PaymentType;
use App\Constants\ResponseType;
use App\Constants\UserMetaKeys;
use App\Errors\UserFriendlyException;
use App\Invoice;
use App\Node;
use App\NodeGroup;
use App\Order;
use App\Transaction;
use App\User;
use App\UserMeta;
use Cache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BillingUtils
{
    /**
     * @param User $user
     * @return array
     * @throws UserFriendlyException
     */
    public static function compileDetails (User $user)
    {
        try
        {
            $addrLine1 = UserMeta::loadMeta($user, UserMetaKeys::AddressLineOne, true)->meta_value;
            $addrLine2 = UserMeta::loadMeta($user, UserMetaKeys::AddressLineTwo, true)->meta_value;
            $city = UserMeta::loadMeta($user, UserMetaKeys::City, true)->meta_value;
            $state = UserMeta::loadMeta($user, UserMetaKeys::State, true)->meta_value;
            $country = UserMeta::loadMeta($user, UserMetaKeys::Country, true)->meta_value;
            $postCode = UserMeta::loadMeta($user, UserMetaKeys::PostCode, true)->meta_value;

            // These are nullable
            $organization = UserMeta::loadMeta($user, UserMetaKeys::Organization);
            $taxId = UserMeta::loadMeta($user, UserMetaKeys::TaxIdentification);

        }
        catch (ModelNotFoundException $e)
        {
            throw new UserFriendlyException(Errors::BILLING_PROFILE_INCOMPLETE, ResponseType::FORBIDDEN);
        }

        return [
            'addrLine1' => $addrLine1,
            'addrLine2' => $addrLine2,
            'city' => $city,
            'state' => $state,
            'country' => $country,
            'postCode' => $postCode,
            'organization' => $organization,
            'taxId' => $taxId
        ];
    }

    /**
     * @param array $compiledDetails
     * @param User $user
     * @return mixed|string
     * @throws UserFriendlyException
     */
    public static function getFormattedUserAddress ($compiledDetails = [], User $user)
    {
        if (empty($compiledDetails))
            $details = static::compileDetails($user);
        else
            $details = $compiledDetails;

        $formattedUserAddress = $details['addrLine1'];
        if (! empty($details['addrLine2']))
            $formattedUserAddress .= PHP_EOL . $details['addrLine2'];
        $formattedUserAddress .= PHP_EOL . $details['city'] . ', ' . $details['state'] . ', ' . $details['postCode'];
        $formattedUserAddress .= PHP_EOL . $details['country'];

        return $formattedUserAddress;
    }

    /**
     * @param Order $order
     * @return float
     */
    public static function getOrderDueAmount (Order $order) : float
    {
        // Let's figure out the amount.
        $items = $order->lineItems
            ->where('status', '!=', OrderStatus::CANCELLED);

        $amount = 0.0;
        foreach ($items as $item)
            $amount += $item->quantity * $item->amount;

        return $amount;
    }

    /**
     * @param Order $order
     * @param Carbon $dueNext
     * @return Invoice
     * @throws \Throwable
     */
    public static function createInvoice (Order $order, Carbon $dueNext) : Invoice
    {
        $invoice = new Invoice();
        $invoice->order_id = $order->id;
        $invoice->user_id = $order->user_id;

        $amount = static::getOrderDueAmount($order);
        $tax = TaxationManager::getTaxAmount($order, $amount);
        $amount += $tax;

        $invoice->type = InvoiceType::STANDARD;
        $invoice->amount = $amount;
        $invoice->tax = $tax;
        $invoice->status = InvoiceStatus::UNPAID;
        $invoice->due_date = $dueNext;
        $invoice->last_reminder_sent = Carbon::now();

        // TODO: Default into USD for now, we'll fix this later
        $invoice->currency = Currency::USD;

        $invoice->saveOrFail();

        $order->last_invoice_id = $invoice->id;
        $order->saveOrFail();

        return $invoice;
    }

    /**
     * @param Invoice $invoice
     * @return mixed
     */
    public static function getInvoiceDueAmount (Invoice $invoice)
    {
        $existingAmount = Transaction::where('invoice_id', $invoice->id)
            ->where('type', PaymentType::CREDIT)
            ->sum('amount');

        $amount = $invoice->amount - $existingAmount;

        return $amount;
    }

    public static function cancelOrder (Order $order)
    {
        foreach ($order->lineItems as $lineItem)
        {
            $lineItem->status = OrderStatus::CANCELLED;
            $lineItem->saveOrFail();
        }

        if ($order->lastInvoice != null)
        {
            $lastInvoice = $order->lastInvoice;
            if ($lastInvoice->status != InvoiceStatus::PAID)
            {
                $lastInvoice->status = InvoiceStatus::CANCELLED;
                $lastInvoice->saveOrFail();
            }
        }

        $order->status = OrderStatus::CANCELLED;
        $order->saveOrFail();
    }

    public static function verifyOrder (Order $order, bool $throwsExceptions = true) : array
    {
        $errors = [];

        foreach ($order->lineItems as $item)
        {
            switch ($item->type)
            {
                case OrderResourceType::NODE:
                    $resource = Node::find($item->resource);
                    break;
                case OrderResourceType::NODE_GROUP:
                    $resource = NodeGroup::find($item->resource);
                    break;
                default:
                    $resource = null;
            }

            if ($resource == null)
            {
                if ($throwsExceptions)
                    throw new UserFriendlyException(Errors::RESOURCE_NOT_FOUND);

                $errors[] = [
                    'id' => $item->id,
                    'reason' => Errors::RESOURCE_NOT_FOUND
                ];

                continue;
            }

            switch ($resource->market_model)
            {
                case NodeMarketModel::UNLISTED:
                    if ($throwsExceptions)
                        throw new UserFriendlyException(Errors::RESOURCE_UNLISTED);

                    $errors[] = [
                        'id' => $item->id,
                        'reason' => Errors::RESOURCE_NOT_FOUND // Purposefully hidden with a falsified error message.
                    ];

                    break;

                case NodeMarketModel::LISTED_DEDICATED:
                    if ($resource->getOrders(OrderStatus::ACTIVE)->count() != 0)
                    {
                        if ($throwsExceptions)
                            throw new UserFriendlyException(Errors::RESOURCE_SOLD_OUT, ResponseType::FORBIDDEN);

                        $errors[] = [
                            'id' => $item->id,
                            'reason' => Errors::RESOURCE_SOLD_OUT
                        ];
                    }

                    break;

                case NodeMarketModel::LISTED_SHARED:
                    // TODO: Enforce the shared limit here once it exists.
                    break;
            }
        }

        return $errors;
    }

    private static function formulateUserPlanCacheKey (User $user)
    {
        return 'core.user.' . $user->id . '.plans';
    }

    public static function getUserPlans (User $user)
    {
        $cacheKey = static::formulateUserPlanCacheKey($user);
        if (Cache::has($cacheKey))
            return Cache::get($cacheKey);


        /** @var array<Order> $orders */
        $orders = Order::findForUser($user->id)
            ->where('status', OrderStatus::ACTIVE)
            ->get();

        $plans = [];
        $definedPlans = config('plans', []);

        /** @var Order $order */
        foreach ($orders as $order)
        {
            foreach ($order->lineItems as $lineItem)
            {
                // Perks are only bestowed based on current, active subscriptions.
                if ($lineItem->status !== OrderStatus::ACTIVE)
                    continue;

                switch ($lineItem->type)
                {
                    case OrderResourceType::ENTERPRISE:
                        if (! in_array(strtolower(OrderResourceType::ENTERPRISE), $plans))
                            $plans[] = strtolower(OrderResourceType::ENTERPRISE);

                        break;

                    case OrderResourceType::NODE:
                        $node = Node::find($lineItem->resource);
                        if ($node != null && $node->plan != null
                            && isset($definedPlans[$node->plan]) && ! in_array($node->plan, $plans))
                            $plans[] = $node->plan;

                        break;

                    case OrderResourceType::NODE_GROUP:
                        $group = NodeGroup::find($lineItem->resource);
                        if ($group != null && $group->plan != null
                            && isset($definedPlans[$group->plan])  && ! in_array($group->plan, $plans))
                            $plans[] = $group->plan;

                        break;
                }
            }
        }

        Cache::put($cacheKey, $plans, env('USER_PLANS_CACHE_MINUTES', 1));

        return $plans;
    }
}