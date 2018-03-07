<?php

namespace App\Http\Controllers\V1;

use App\Constants\Messages;
use App\Constants\ResponseType;
use App\Libraries\PaginationManager;
use App\Libraries\SearchManager;
use App\PromoCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoCodeController extends CRUDController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeResource();

        $rules = [
            'searchId' => 'sometimes|alphanum'
        ];

        $this->validate($request, $rules);

        $queryBuilder = SearchManager::process($request, 'promo_code');

        return PaginationManager::paginate($request, $queryBuilder);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeResource();

        $rules = [
            'code' => 'required',
            'group_id' => 'required',
            'onetime' => 'required',
            'amount' => 'required'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        $promoCode = new PromoCode();
        $promoCode->code = $input['code'];
        $promoCode->group_id = $input['group_id'];
        $promoCode->onetime = $input['onetime'];
        $promoCode->amount = $input['amount'];

        $promoCode->saveOrFail();

        return $this->respond($promoCode->toArray(), [], Messages::PROMO_CODE_CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rules = [
            'code' => 'required',
            'group_id' => 'required',
            'onetime' => 'required',
            'amount' => 'required'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        $promoCode = PromoCode::findOrFail($id);

        $this->authorizeResource($promoCode);

        foreach ($input as $key => $value)
            $promoCode->$key = $value;

        $promoCode->saveOrFail();

        return $this->respond($promoCode->toArray());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $promoCode = PromoCode::findOrFail($id);
        $this->authorizeResource($promoCode);

        $promoCode->delete();

        return $this->respond(null, [], Messages::PROMO_CODE_REMOVED, ResponseType::NO_CONTENT);
    }

    public function show(Request $request, int $id, String $action = null): JsonResponse
    {
        $promoCode = PromoCode::findOrFail($id);
        $this->authorizeResource($promoCode);

        return $this->respond($promoCode->toArray());
    }

}
