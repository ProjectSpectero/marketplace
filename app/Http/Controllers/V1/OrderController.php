<?php

namespace App\Http\Controllers\V1;

use App\Constants\Currency;
use App\Constants\Errors;
use App\Constants\Events;
use App\Constants\InvoiceStatus;
use App\Constants\Messages;
use App\Constants\NodeMarketModel;
use App\Constants\NodeSyncStatus;
use App\Constants\OrderResourceType;
use App\Constants\OrderStatus;
use App\Constants\PaymentProcessor;
use App\Constants\ResponseType;
use App\Constants\ServiceType;
use App\Constants\UserRoles;
use App\EnterpriseResource;
use App\Errors\UserFriendlyException;
use App\Events\BillingEvent;
use App\Invoice;
use App\Libraries\BillingUtils;
use App\Libraries\FraudCheckManager;
use App\Libraries\PaginationManager;
use App\Libraries\ProvisionedResourceResolver;
use App\Libraries\SearchManager;
use App\Libraries\TaxationManager;
use App\Libraries\Utility;
use App\Node;
use App\NodeGroup;
use App\Order;
use App\OrderLineItem;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends CRUDController
{
    public function __construct()
    {
        $this->resource = 'order';
    }

    public function show (Request $request, int $id, String $action = null) : JsonResponse
    {
        /** @var Order $order */
        $order = Order::findOrFail($id);

        $this->authorizeResource($order);

        switch ($action)
        {
            case 'invoices':
                return PaginationManager::paginate($request, Invoice::findForOrder($order)->noEagerLoads());

            case 'verify':
                if (! in_array($order->status, OrderStatus::getFixable()))
                    throw new UserFriendlyException(Errors::RESOURCE_STATUS_MISMATCH);

                $errors = BillingUtils::verifyOrder($order, false);
                $message = count($errors) > 0 ? Errors::ORDER_VERIFICATION_FAILED : null;
                $statusCode = count($errors) > 0 ? ResponseType::UNPROCESSABLE_ENTITY : ResponseType::OK;

                return $this->respond(null, BillingUtils::verifyOrder($order, false), $message, $statusCode);

            case 'resources':
                return $this->respond(ProvisionedResourceResolver::resolve($order));

            default:
                $data = $order->toArray();
                $data['easy_enabled'] = $order->canBypassBillingProfileCheck();

                return $this->respond($data);
        }
    }

    public function self(Request $request, String $action = null)
    {
        $rules = [
            'searchId' => 'sometimes|alphanum'
        ];
        $this->validate($request, $rules);

        $user = $request->user();
        $query = SearchManager::process($request, 'order', Order::findForUser($user->id));

        switch ($action)
        {
            case 'active':
                $query->where('status', OrderStatus::ACTIVE);
                break;

            case strtolower(OrderResourceType::ENTERPRISE):
                $query->join('order_line_items', 'orders.id', '=', 'order_line_items.order_id')
                    ->where('order_line_items.type', '=', OrderResourceType::ENTERPRISE);

                $query->select('orders.*')->distinct()->groupBy('orders.id');

                break;
        }

        return PaginationManager::paginate($request, $query);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeResource();

        $rules = [
            'searchId' => 'sometimes|alphanum'
        ];

        $this->validate($request, $rules);

        $queryBuilder = SearchManager::process($request, 'order');

        return PaginationManager::paginate($request, $queryBuilder);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeResource();

        $rules = [
            'user_id' => 'required|integer|exists:users,id',
            'status' => [ 'required', Rule::in(OrderStatus::getConstants()) ],
            'term' => 'required|integer',
            'due_next' => 'required|date',
            'items' => 'array|min:1',
            'items.*.type' =>  Rule::in(OrderResourceType::getOrderable()),
            'items.*.id' => 'required|numeric',
            'meta.term' => 'required|in:30,365'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        /** @var User $user */
        $user = User::findOrLogAndFail($input['user_id']);

        $order = $this->createOrder($user, $input['term'], Carbon::now(), $input['items']);

        $order->status = $input['status'];
        $order->due_next = $input['due_next'];
        $order->saveOrFail();

        event(new BillingEvent(Events::ORDER_CREATED, $order));

        return $this->respond($order->toArray(), [], Messages::ORDER_CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rules = [
            'status' => [ 'sometimes', Rule::in(OrderStatus::getConstants()) ],
            'subscription_reference' => 'sometimes|alpha_dash',
            'subscription_provider' => [ 'required_with:subscription_reference', Rule::in(PaymentProcessor::getConstants()) ],
            'term' => 'sometimes|integer',
            'due_next' => 'sometimes|date'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        $order = Order::findOrFail($id);

        $this->authorizeResource($order);

        foreach ($input as $key => $value)
            $order->$key = $value;

        $order->saveOrFail();

        event(new BillingEvent(Events::ORDER_REVERIFY, $order));

        return $this->respond($order->toArray(), [], Messages::ORDER_UPDATED);
    }

    public function makeOrderDeliverable (Request $request, int $id) : JsonResponse
    {
        /*
         * Flow:
         *  1. If count(erros) == count(order->elements), cancel the whole order, we can't fix it.
         *  2. Otherwise, remove the problematic elements from the order.
         *  3. Fix the invoice at the end of it all.
         */

        $order = Order::findOrFail($id);
        $this->authorizeResource($order, 'order.makeOrderDeliverable');

        if (! in_array($order->status, OrderStatus::getFixable()))
            throw new UserFriendlyException(Errors::RESOURCE_STATUS_MISMATCH);

        $errors = BillingUtils::verifyOrder($order, false);

        if (count($errors) == 0)
            throw new UserFriendlyException(Errors::ORDER_ALREADY_VERIFIED);

        if (count($errors) == $order->lineItems->count())
        {
            // This also removes the invoice.
            BillingUtils::cancelOrder($order);
            throw new UserFriendlyException(Errors::ACTION_NOT_SUPPORTED);
        }

        if (count($errors) !== 0)
            $changed = true;
        else
            $changed = false;

        foreach ($errors as $error)
        {
            // We don't really care why it failed, just that it did.
            OrderLineItem::destroy($error['id']);
        }

        // Refresh the line items.
        $order->load('lineItems');

        if ($changed)
        {
            // Let's fix the invoice.
            /** @var Invoice $invoice */
            $invoice = $order->lastInvoice;
            $dueToday = BillingUtils::getOrderDueAmount($order);
            $invoice->amount = $dueToday + TaxationManager::getTaxAmount($order, $dueToday);
            $invoice->saveOrFail();
        }

        return $this->respond($order->toArray());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var Order $order */
        $order = Order::findOrFail($id);
        $this->authorizeResource($order);

        BillingUtils::cancelOrder($order);

        return $this->respond(null, [], Messages::ORDER_DELETED, ResponseType::NO_CONTENT);
    }

    public function createOrder (User $user, int $term, Carbon $dueNext, array $items)
    {
        $order = new Order();
        $order->user_id = $user->id;
        $order->status = OrderStatus::PENDING;
        $order->term = $term;
        $order->due_next = $dueNext;
        $order->accessor = Utility::getRandomString() . ':' . Utility::getRandomString();
        $order->saveOrFail();

        $this->populateLineItems($items, $order, $term, true, $dueNext);

        return $order;
    }

    private function validateItemsAgainstCurrentUser (User $user, array $items)
    {
        // MAR-229: don't try to meddle in non-singular orders, this was more or less devised just to prevent duplicate single resource (i.e: pro / vast majority of normal) orders.
        if (count($items) > 1)
            return null;

        $item = $items[0];

        /** @var OrderLineItem $existing */
        $existing = OrderLineItem::join('orders', 'order_line_items.order_id', '=', 'orders.id')
            ->where('order_line_items.resource', $item['id'])
            ->where('order_line_items.type', $item['type'])
            ->where('order_line_items.status', OrderStatus::PENDING)
            ->where('orders.status', OrderStatus::PENDING)
            ->where('orders.user_id', $user->id)
            ->select([ 'order_line_items.*' ])
            ->get();

        $count = $existing->count();

        if ($count > 0)
        {
            $first = $existing->first();

            if ($existing->count() > 1)
                \Log::warning("User #$user->id has $count PENDING order(s) against an item. We're going with the first one (OLI #$first->id).", [ 'item' => $item ] );

            return $first->order;
        }

        return null;
    }

    private function populateLineItems (array $items, Order $order,
                                        int $term, bool $createInvoice = true,
                                        Carbon $dueNext = null)
    {
        $orderId = $order->id;
        $lineItems = [];
        foreach ($items as $item)
        {
            $type = $item['type'];
            $quantity = 1;

            switch($type)
            {
                case OrderResourceType::NODE:
                    /** @var Node $resource */
                    $resource = Node::findOrFail($item['id']);

                    /*
                     * Flow:
                     * Check if node is part of a group, deny if so.
                     * Check if node has any active orders, and its market model is LISTED_DEDICATED (enforced later)
                     * Check if node's model is UNLISTED, deny if yes
                     */
                    if ($resource->group != null)
                    {
                        BillingUtils::cancelOrder($order);
                        throw new UserFriendlyException(Errors::NODE_BELONGS_TO_GROUP . ':' . $resource->id);
                    }

                    break;
                case OrderResourceType::NODE_GROUP:

                    /** @var NodeGroup $resource */
                    $resource = NodeGroup::findOrFail($item['id']);
                    /*
                     * Flow:
                     * Check the model, if UNLISTED, deny
                     * If LISTED_DEDICATED, check if at least one active order exists. Deny if it does (enforced later)
                     */
                    break;
                default:
                    // TODO: Add handling for ent order type here, eventually.
                    BillingUtils::cancelOrder($order);
                    throw new UserFriendlyException(Errors::RESOURCE_NOT_FOUND);
            }

            switch ($resource->market_model)
            {
                case NodeMarketModel::UNLISTED:
                    BillingUtils::cancelOrder($order);
                    throw new UserFriendlyException(Errors::RESOURCE_NOT_FOUND); // Prevent ID enumeration

                case NodeMarketModel::LISTED_DEDICATED:
                    if ($resource->getOrders(OrderStatus::ACTIVE)->count() != 0)
                    {
                        BillingUtils::cancelOrder($order);
                        throw new UserFriendlyException(Errors::RESOURCE_SOLD_OUT);
                    }
            }

            if ($resource->user_id == $order->user_id)
            {
                BillingUtils::cancelOrder($order);
                throw new UserFriendlyException(Errors::OWN_RESOURCE);
            }

            // Single item price calculation
            $plans = config('plans', []);
            $price = $resource->price;
            if ($term > 30)
            {
                $price = ($price / 30) * $term;

                // Now, let's see if this shit happens to belong to some plan which has a discount.
                if ($resource->plan != null)
                {
                    // Plan associated resources may NOT be combined into an order, they must be individually bought.
                    if (count($items) !== 1)
                    {
                        BillingUtils::cancelOrder($order);
                        throw new UserFriendlyException(Errors::DISCREET_ORDER_REQUIRED);
                    }

                    // Ok, apparently it does. Does this plan still exist?
                    if (isset($plans[$resource->plan]))
                    {
                        $plan = $plans[$resource->plan];

                        // Ok, apparently it does. Let's check if this crap qualifies for a yearly discount
                        if ($term >= 365 && isset($plan['yearly_discount_pct'])
                            && is_numeric($plan['yearly_discount_pct']) && $plan['yearly_discount_pct'] < 1.0)
                        {
                            $price -= $price * $plan['yearly_discount_pct'];
                            $price = floor($price); // This removes odd fractions, though it does result in a slightly higher PCT discount as well.

                            if ($price < 0)
                                $price = 0;
                        }
                    }
                    // If not, we do nothing. Just silently charge the shit at full price.
                }
            }

            $identifier = empty($resource->friendly_name) ? 'No identifier' : $resource->friendly_name;
            $plan = empty($resource->plan) ? 'no associated plans' : $resource->plan;

            $lineItem = new OrderLineItem();
            $lineItem->description = sprintf("%s (%s)", $identifier, $plan);
            $lineItem->order_id = $orderId;
            $lineItem->type = $type;
            $lineItem->resource = $resource->id;
            $lineItem->quantity = $quantity;
            $lineItem->amount = $price;
            $lineItem->status = OrderStatus::PENDING;
            $lineItem->sync_status = NodeSyncStatus::PENDING_SYNC;

            $lineItem->saveOrFail();

            $lineItems[] = $lineItem;
        }

        if ($createInvoice)
        {
            $invoice = BillingUtils::createInvoice($order, $dueNext);

            $order->last_invoice_id = $invoice->id;
            $order->saveOrFail();
        }
    }

    public function cart(Request $request)
    {
        $this->authorizeResource(null, 'order.cart');

        // Why this useless call when we don't care about the billing details? Because this checks for billing profile completeness.
        // Will send people a nice 403 if they try to submit orders without a complete billing profile.
        // MAR-194: pre-order billing-profile-completeness check is now disabled. This check is now enforced on payment instead.
        // BillingUtils::compileDetails($request->user());

        $rules = [
            'items' => 'array|min:1',
            'items.*.type' =>  Rule::in(OrderResourceType::getOrderable()),
            'items.*.id' => 'required|numeric',
            'meta.term' => 'required|in:30,365'
        ];

       $this->validate($request, $rules);
       $input = $this->cherryPick($request, $rules);
       unset($input['items']['*']);

       $user = $request->user();

        if (! FraudCheckManager::stageOne($user))
            throw new UserFriendlyException(Errors::ORDER_DENIED, ResponseType::FORBIDDEN);

        $term = $input['meta']['term'];
        $items = $input['items'];

        $returnedMessage = null;

        $existingOrder = $this->validateItemsAgainstCurrentUser($user, $items);


        if ($existingOrder != null)
        {
            $order = $existingOrder;
            $returnedMessage = Errors::ORDER_REUSED;
        }
        else
        {
            $order = $this->createOrder($request->user(), $term, Carbon::now(), $items);

            // This forces a reload of all relations on an as-needed basis.
            $order->load('lastInvoice');

            event(new BillingEvent(Events::ORDER_CREATED, $order));
        }

        return $this->respond($order->toArray(), [], $returnedMessage);
    }

    public function regenerateAccessor (Request $request, int $id)
    {
        /** @var Order $order */
        $order = Order::findOrFail($id);
        $this->authorizeResource($order, 'order.update');

        // TODO: Build support for full ent handling, and at that point enable accessor updates.
        if ($order->isEnterprise())
            throw new UserFriendlyException(Errors::CONTACT_ACCOUNT_REPRESENTATIVE, ResponseType::FORBIDDEN);

        // Alright, this is the owner. Let's lookup its line items and reset their sync statuses one by one.
        /** @var OrderLineItem $lineItem */
        foreach ($order->lineItems as $lineItem)
        {
            $lineItem->sync_status = NodeSyncStatus::PENDING_SYNC;
            $lineItem->saveOrFail();
        }

        // Great, let's tag the order with the new accessor as well.
        $order->accessor = Utility::getRandomString(1) . ':' . Utility::getRandomString(1);
        $order->saveOrFail();

        return $this->respond($order->toArray());
    }

}
