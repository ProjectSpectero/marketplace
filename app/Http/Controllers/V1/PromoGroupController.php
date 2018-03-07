<?php

namespace App\Http\Controllers\V1;

use App\Constants\Messages;
use App\Constants\ResponseType;
use App\Libraries\PaginationManager;
use App\Libraries\SearchManager;
use App\PromoGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoGroupController extends CRUDController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeResource();

        $rules = [
            'searchId' => 'sometimes|alphanum'
        ];

        $this->validate($request, $rules);

        $queryBuilder = SearchManager::process($request, 'promo_group');

        return PaginationManager::paginate($request, $queryBuilder);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeResource();

        $rules = [
            'name' => 'required',
            'applications' => 'required'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        $promoGroup = new PromoGroup();
        $promoGroup->name = $input['name'];
        $promoGroup->applications = $input['applications'];

        $promoGroup->saveOrFail();

        return $this->respond($promoGroup->toArray(), [], Messages::PROMO_GROUP_CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rules = [
            'name' => 'required',
            'applications' => 'required'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        $promoGroup = PromoGroup::findOrFail($id);

        $this->authorizeResource($promoGroup);

        foreach ($input as $key => $value)
            $promoGroup->$key = $value;

        $promoGroup->saveOrFail();

        return $this->respond($promoGroup->toArray());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $promoGroup = PromoGroup::findOrFail($id);
        $this->authorizeResource($promoGroup);

        $promoGroup->delete();

        return $this->respond(null, [], Messages::PROMO_GROUP_REMOVED, ResponseType::NO_CONTENT);
    }

    public function show(Request $request, int $id, String $action = null): JsonResponse
    {
        $promoGroup = PromoGroup::findOrFail($id);
        $this->authorizeResource($promoGroup);

        return $this->respond($promoGroup->toArray());
    }

}
