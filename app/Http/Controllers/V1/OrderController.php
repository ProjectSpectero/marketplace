<?php

namespace App\Http\Controllers\V1;

use App\Constants\Messages;
use App\Constants\OrderResourceType;
use App\Constants\ResponseType;
use App\Invoice;
use App\Libraries\PaginationManager;
use App\Libraries\SearchManager;
use App\Order;
use App\OrderLineItem;
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
            'subscription_reference' => 'required',
            'subscription_provider' => 'required',
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

    public function cart(Request $request)
    {
        $rules = $this->getCartRules($request);

       $this->validate($request, $rules);
       $items = $this->cherryPick($request, $rules);
    }

    private function getCartRules(Request $request)
    {
        $rules = [
            'items' => 'array|min:1',
            'meta' => 'array|min:1'
        ];
        foreach ($request->get('items') as $key => $value)
        {
            $rules['items.' .$key. '.type'] = Rule::in(OrderResourceType::getConstants());
            $rules['items.' .$key. '.id'] = 'required|numeric';
        }
        foreach ($request->get('meta') as $key => $value)
            $rules['meta.' .$key. '.term'] = 'required|equals:30';

        return $rules;
    }
}
