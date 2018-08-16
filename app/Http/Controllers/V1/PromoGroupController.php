<?php

namespace App\Http\Controllers\V1;

use App\Constants\Messages;
use App\Constants\ResponseType;
use App\Errors\NotSupportedException;
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
            'name' => 'required|alpha_dash_spaces',
            'usage_limit' => 'required|integer'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        $promoGroup = new PromoGroup();
        $promoGroup->name = $input['name'];
        $promoGroup->usage_limit = $input['usage_limit'];

        $promoGroup->saveOrFail();

        return $this->respond($promoGroup->toArray(), [], Messages::PROMO_GROUP_CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $promoGroup = PromoGroup::findOrFail($id);
        $this->authorizeResource($promoGroup);

        $rules = [
            'name' => 'required|alpha_dash',
            'usage_limit' => 'required|integer'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        foreach ($input as $key => $value)
            $promoGroup->$key = $value;

        $promoGroup->saveOrFail();

        return $this->respond($promoGroup->toArray());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        // TODO: Group destruction has implications, figure out what those are before allowing this.
        // It is likely we may not be able to support it at all.
        // Or we'll need to remove all codes that are a part of the group when this is called alongside, that's the only way to really work with it.
        // But if we do that, PromoUsage entries now become invalid, and past usage records may not be nuked.

        throw new NotSupportedException();

        /*
         *          $promoGroup = PromoGroup::findOrFail($id);
                    $this->authorizeResource($promoGroup);
                    $promoGroup->delete();
                    return $this->respond(null, [], Messages::PROMO_GROUP_REMOVED, ResponseType::NO_CONTENT);
         */
    }

    public function show(Request $request, int $id, String $action = null): JsonResponse
    {
        $promoGroup = PromoGroup::findOrFail($id);
        $this->authorizeResource($promoGroup);

        return $this->respond($promoGroup->toArray());
    }

}
