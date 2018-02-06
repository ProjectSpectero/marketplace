<?php

namespace App\Http\Controllers\V1;

use App\Constants\Messages;
use App\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends CRUDController
{
    public function store(Request $request): JsonResponse
    {
        $rules = [
            'user_id' => 'required',
            'status' => 'required',
            'subscription_reference' => 'required',
            'subscription_provider' => 'required',
            'term' => 'required',
            'due_next' => 'required'
        ];

        $this->validate($request, $rules);

        $order = new Order();
        $order->user_id = $request->get('user_id');
        $order->status = $request->get('status');
        $order->subscription_reference = $request->get('subscription_reference');
        $order->subscription_provider = $request->get('subscription_provider');
        $order->term = $request->get('term');
        $order->due_next = $request->get('due_next');

        $order->saveOrFail();

        return $this->respond($order->toArray(), [], Messages::ORDER_CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        foreach ($request->all() as $key => $value)
            $order->$key = $value;

        $order->saveOrFail();

        return $this->respond($order->toArray(), [], Messages::ORDER_UPDATED);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        $order->delete;

        return $this->respond(null, [], Messages::ORDER_DELETED, ResponseType::NO_CONTENT);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        return $this->respond($order->toArray());
    }

}
