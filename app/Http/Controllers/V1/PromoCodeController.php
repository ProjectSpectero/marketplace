<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\Messages;
use App\Constants\ResponseType;
use App\Errors\UserFriendlyException;
use App\Libraries\PaginationManager;
use App\Libraries\SearchManager;
use App\PromoCode;
use App\PromoGroup;
use App\PromoUsage;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Aware that returning 404 on a paramter not existing is shitty UX, but this is not a user endpoint for the most part.
// Apply is the only thing users deal with, the rest are for peasants, sorry, A D M I N I S T R A T O R S.
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
            'code' => 'required|alpha_dash|unique:promo_codes',
            'group_id' => 'required|integer',
            'usage_limit' => 'required|integer',
            'amount' => 'required|numeric',
            'expires' => 'sometimes|date_format:Y-m-d'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        // Let's make sure this group actually exists.
        PromoGroup::findOrFail($input['group_id']);

        $promoCode = new PromoCode();
        $promoCode->code = $input['code'];
        $promoCode->group_id = $input['group_id'];
        $promoCode->usage_limit = $input['usage_limit'];
        $promoCode->amount = $input['amount']; // TODO: Add support for currency tracking here.

        // If expiry is not given, the promo code is valid for a week only.
        if ($request->has('expires'))
            $promoCode->expires = $input['expires'];
        else
            $promoCode->expires = Carbon::now()->addDays(7);

        $promoCode->saveOrFail();

        return $this->respond($promoCode->toArray(), [], Messages::PROMO_CODE_CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rules = [
            'code' => 'required|alpha_dash|unique:promo_codes,code,' . $id,
            'group_id' => 'required|integer',
            'usage_limit' => 'required|integer',
            'amount' => 'required|numeric',
            'expires' => 'sometimes|date_format:Y-m-d'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        $promoCode = PromoCode::findOrFail($id);

        $this->authorizeResource($promoCode);

        foreach ($input as $key => $value)
        {
            // If sometimes rules are involved, you're expected to do this to not insert junk.
            if ($request->has($key))
                $promoCode->$key = $value;
        }

        // Let's make sure this group actually exists.
        PromoGroup::findOrFail($input['group_id']);

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
        $rules = [
            'code' => 'required|alpha_dash'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        /** @var PromoCode $code */
        $code = PromoCode::where('code', $input['code'])->firstOrFail();

        /** @var User $user */
        $user = $request->user();

        if ($code->enabled != true)
            throw new UserFriendlyException(Errors::PROMO_CODE_INVALID);

        if ($code->usage_limit <= 0)
            throw new UserFriendlyException(Errors::PROMO_ACTIVATION_LIMIT_REACHED);

        $currentGroup = $code->group;
        $currentGroupActivations = 0;

        /** @var PromoUsage $promoUsage */
        foreach ($user->promoUsages as $promoUsage)
        {
            // Idea is to see if the user has activated a code belonging to $code's group earlier.
            // This loop is probably fine, we don't think a user will have manay codes applied. One or two in the lifetime of the account is probably a good guess.
            // We also validate that this SAME code has not previously been activated by them.

            /** @var PromoCode $usedCode */
            $usedCode = $promoUsage->code;

            if ($usedCode->id == $code->id)
                throw new UserFriendlyException(Errors::PROMO_CODE_ALREADY_USED);

            if ($usedCode->group->id == $currentGroup->id)
                $currentGroupActivations++;
        }

        if ($currentGroupActivations >= $currentGroup->usage_limit)
            throw new UserFriendlyException(Errors::PROMO_GROUP_LIMIT_REACHED);



        // Why a txn? EVERY ONE of these updates need to succeed, otherwise our state is inconsistent.
        \DB::transaction(function() use ($user, $code)
        {
            $promoUsage = new PromoUsage();
            $promoUsage->code_id = $code->id;
            $promoUsage->user_id = $user->id;
            $promoUsage->saveOrFail();

            // Let's reduce it by one to show that it's been activated once.
            $code->decrement('usage_limit', 1);

            // Let's add credit to the user. We can't use increment here, that only deals with ints <.<'
            $user->credit = $user->credit + $code->amount;
            $user->saveOrFail();
        });

        return $this->respond(null, [], Messages::PROMO_CODE_APPLIED);
    }

}
