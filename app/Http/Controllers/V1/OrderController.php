<?php

namespace App\Http\Controllers\V1;

use App\Constants\Currency;
use App\Constants\Errors;
use App\Constants\Events;
use App\Constants\InvoiceStatus;
use App\Constants\Messages;
use App\Constants\NodeMarketModel;
use App\Constants\OrderResourceType;
use App\Constants\OrderStatus;
use App\Constants\PaymentProcessor;
use App\Constants\ResponseType;
use App\Errors\UserFriendlyException;
use App\Events\BillingEvent;
use App\Invoice;
use App\Libraries\PaginationManager;
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
        $order = Order::findOrFail($id);
        $this->authorizeResource($order);

        switch ($action)
        {
            case 'invoices':
                return PaginationManager::paginate($request, Invoice::findForOrder($order)->noEagerLoads());
            default:
                return $this->respond($order->toArray());
        }
    }

    public function self(Request $request)
    {
        $user = $request->user();
        return PaginationManager::paginate($request, Order::findForUser($user->id));
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
            'status' => 'required',
            'subscription_reference' => 'sometimes|alpha_dash',
            'subscription_provider' => [ 'required_with:subscription_reference', Rule::in(PaymentProcessor::getConstants()) ],
            'term' => 'required',
            'due_next' => 'required'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        $order = new Order();
        $order->user_id = $request->user()->id;
        $order->status = $input['status'];
        $order->subscription_reference = $input['subscription_reference'];
        $order->subscription_provider = $input['subscription_provider'];
        $order->term = $input['term'];
        $order->due_next = $input['due_next'];

        $order->saveOrFail();

        return $this->respond($order->toArray(), [], Messages::ORDER_CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rules = [
            'status' => 'required',
            'subscription_reference' => 'required',
            'subscription_provider' => 'required',
            'term' => 'required',
            'due_next' => 'required'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        $order = Order::findOrFail($id);

        $this->authorizeResource($order);

        foreach ($input as $key => $value)
            $order->$key = $value;

        $order->user_id = $request->user()->id;

        $order->saveOrFail();

        return $this->respond($order->toArray(), [], Messages::ORDER_UPDATED);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $this->authorizeResource($order);

        $order->delete;

        return $this->respond(null, [], Messages::ORDER_DELETED, ResponseType::NO_CONTENT);
    }

    public function createOrder (User $user, int $term, Carbon $dueNext, Array $items)
    {

        $order = new Order();
        $order->user_id = $user->id;
        $order->status = OrderStatus::AUTOMATED_FRAUD_CHECK;
        $order->term = $term;
        $order->due_next = $dueNext;
        $order->saveOrFail();

        $this->populateLineItems($items, $order, true, $dueNext);

        return $order;
    }

    private function populateLineItems (array $items, Order $order, bool $createInvoice = true, Carbon $dueNext = null)
    {
        $orderId = $order->id;
        $lineItems = [];
        foreach ($items as $item)
        {
            $type = $item['type'];
            $quantity = 1;
            $amount = 0.0;

            switch($type)
            {
                case OrderResourceType::NODE:
                    /** @var Node $resource */
                    $resource = Node::findOrFail($item['id']);

                    /*
                     * Flow:
                     * Check if node is part of a group, deny if so.
                     * Check if node has any active orders, and its market model is LISTED_DEDICATED
                     * Check if node's model is UNLISTED, deny if yes
                     */
                    if ($resource->group != null)
                        throw new UserFriendlyException(Errors::NODE_BELONGS_TO_GROUP . ':' . $resource->id);
                    break;
                case OrderResourceType::NODE_GROUP:

                    /** @var NodeGroup $resource */
                    $resource = NodeGroup::findOrFail($item['id']);
                    /*
                     * Flow:
                     * Check the model, if UNLISTED, deny
                     * If LISTED_DEDICATED, check if at least one active order exists. Deny if it does
                     */
                    break;
                default:
                    throw new UserFriendlyException(Errors::RESOURCE_NOT_FOUND);
            }

            switch ($resource->market_model)
            {
                case NodeMarketModel::UNLISTED:
                    throw new UserFriendlyException(Errors::RESOURCE_UNLISTED);

                case NodeMarketModel::LISTED_DEDICATED:
                    if ($resource->getOrders(OrderStatus::ACTIVE)->count() != 0)
                        throw new UserFriendlyException(Errors::RESOURCE_SOLD_OUT);
            }

            $lineItem = new OrderLineItem();
            $lineItem->description = $resource->friendly_name;
            $lineItem->order_id = $orderId;
            $lineItem->type = $type;
            $lineItem->resource = $resource->id;
            $lineItem->quantity = $quantity;
            $lineItem->amount = $resource->price;

            // User's id number + this (below) will become their login credentials for this line item if the order is fulfilled.
            $lineItem->access_password = Utility::getRandomString();

            $amount += $resource->price * $quantity;

            $lineItem->saveOrFail();

            $lineItems[] = $lineItem;
        }

        if ($createInvoice)
        {
            $invoice = new Invoice();
            $invoice->order_id = $orderId;
            $invoice->user_id = $order->user_id;
            $invoice->amount = $amount;
            $invoice->tax = TaxationManager::getTaxAmount($order);
            $invoice->status = InvoiceStatus::UNPAID;
            $invoice->due_date = $dueNext;

            // TODO: Default into USD for now, we'll fix this later
            $invoice->currency = Currency::USD;

            $invoice->saveOrFail();

            $order->last_invoice_id = $invoice->id;
            $order->saveOrFail();
        }
    }

    public function cart(Request $request)
    {
        $this->authorizeResource(null, 'order.cart');

        $rules = [
            'items' => 'array|min:1',
            'items.*.type' =>  Rule::in(OrderResourceType::getConstants()),
            'items.*.id' => 'required|numeric',
            'meta.term' => 'required|in:30'
        ];

       $this->validate($request, $rules);
       $input = $this->cherryPick($request, $rules);
       unset($input['items']['*']);

       $order = $this->createOrder($request->user(), $input['meta']['term'], Carbon::now(), $input['items']);

       event(new BillingEvent(Events::ORDER_CREATED, $order));

       return $this->respond($order->toArray());
    }

}
