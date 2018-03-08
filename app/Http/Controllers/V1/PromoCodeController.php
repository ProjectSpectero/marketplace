<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\Messages;
use App\Constants\ResponseType;
use App\Errors\UserFriendlyException;
use App\Libraries\PaginationManager;
use App\Libraries\SearchManager;
use App\PromoCode;
use App\PromoUsage;
use App\User;
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

        if (! empty($promoCode->usages))
        {
            $promoCode->enabled = false;
            $promoCode->saveOrFail();

            return $this->respond($promoCode->toArray(), [], Messages::PROMO_CODE_DISABLED);
        }
        else
            $promoCode->delete();

        return $this->respond(null, [], Messages::PROMO_CODE_REMOVED, ResponseType::NO_CONTENT);
    }

    public function show(Request $request, int $id, String $action = null): JsonResponse
    {
        $promoCode = PromoCode::findOrFail($id);
        $this->authorizeResource($promoCode);

        return $this->respond($promoCode->toArray());
    }

    public function apply(Request $request)
    {
        $code = $request->get('code');
        $user = $request->user();

        $promoCode = PromoCode::where('code', $code)->first();
        $usages = PromoUsage::where('code_id', $promoCode->id)->where('user_id', $user->id)->get();

        if (! empty($usages) && $promoCode->onetime == true)
            throw new UserFriendlyException(Errors::PROMO_CODE_ALREADY_USED);

        $applications = $promoCode->group->applications;

        if ($usages > $applications)
            throw new UserFriendlyException(Errors::PROMO_CODE_LIMIT_REACHED);

        $promoUsage = new PromoUsage();
        $promoUsage->code_id = $promoCode->id;
        $promoUsage->user_id = $user->id;
        $promoUsage->saveOrFail();

        \DB::table('users')->where('id', $user->id)->increment('credit', $promoCode->amount);

        return $this->respond(null, [], Messages::PROMO_CODE_APPLIED);
    }

}
