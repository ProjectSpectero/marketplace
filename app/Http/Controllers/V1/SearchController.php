<?php


namespace App\Http\Controllers\V1;


use App\Libraries\Utility;
use App\Models\Opaque\SearchEntity;
use App\Models\Opaque\SearchResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SearchController extends V1Controller
{
    public function handleSearch (Request $request) : JsonResponse
    {
        // Validation has to be done in two stages, because to know the valid fields, we need to know the resource first
        $resourceValidationRule = [
            'resource' => [ 'required', Rule::in(config('search.resources')) ]
        ];
        $this->validate($request, $resourceValidationRule);

        // Figure out what fields are marked searchable for this model
        $resource = $request->input('resource');
        $searchAbleFields = $this->getSearchAbleFieldsForResource($resource);

        $rules = [
            'expires' => 'sometimes|numeric|max:' . config('search.maxExpiry', 600),
            'rules' => 'required', // TAKE NOTE OF THIS, you need to validate the field itself (here) and all members (below)
            'rules.*.field' => [ 'required', 'alpha_dash', Rule::in($searchAbleFields) ],
            'rules.*.operator' => [ 'required', Rule::in(config('search.operators')) ],
            'rules.*.value' => 'required'
        ];
        $this->validate($request, $rules);

        // Ok, someone apparently figured out our total shitshow of a search format. Hurray, let's store it and return the right identifier.
        $expires = $request->has('expires') ? $request->input('expires') : config('search.maxExpiry', 600);
        $searchId = Utility::getRandomString();

        $searchEntity = new SearchEntity();
        $searchEntity->resource = $resource;
        $searchEntity->rules = $request->input('rules');

        \Cache::put($this->generateSearchKey($searchId), $searchEntity, $expires);

        $response = new SearchResponse();
        $response->searchId = $searchId;

        return $this->respond($response->toArray());
    }

    private function generateSearchKey (String $id) : string
    {
        return "searches." . $id;
    }

    private function getSearchAbleFieldsForResource (String $resource) : array
    {
        $key = 'searches.fields.' . $resource;
        if (\Cache::has($key))
            return \Cache::get($key, []);

        $modelFieldCacheMinutes = config('search.modelFieldCacheMinutes', 30);
        $underlyingModel = Utility::getModelFromResourceSlug($resource);
        $value = property_exists($underlyingModel, 'searchAble') ? $underlyingModel->searchAble : [];
        \Cache::put($key, $value, $modelFieldCacheMinutes);

        return $value;
    }
}