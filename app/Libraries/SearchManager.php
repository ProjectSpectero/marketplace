<?php


namespace App\Libraries;


use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Errors\UserFriendlyException;
use App\Models\Opaque\SearchEntity;
use Illuminate\Database\Eloquent\Collection;

class SearchManager
{
    /**
     * @param String $searchId
     * @param String $caller
     * @return Collection
     * @throws UserFriendlyException
     */
    public static function process (String $searchId, String $caller) : Collection
    {
        /** @var SearchEntity $searchEntity */
        $key = self::generateSearchKey($searchId);
        $searchEntity = \Cache::has($key) ? \Cache::get($key) : null;

        if ($searchEntity == null)
            throw new UserFriendlyException(Errors::SEARCH_ID_INVALID_OR_EXPIRED, ResponseType::UNPROCESSABLE_ENTITY);

        if ($searchEntity->resource !== $caller)
            throw new UserFriendlyException(Errors::SEARCH_RESOURCE_MISMATCH);


        $model = Utility::getModelFromResourceSlug($caller);
        $constraints = [];
        foreach ($searchEntity->rules as $rule)
        {
            if ($rule['operator'] == 'LIKE')
                $rule['value'] = '%' . $rule['value'] . '%';

            $constraints[] = [ $rule['field'], $rule['operator'], $rule['value'] ];
        }


        return $model->where($constraints)->get();
    }

    public static function getSearchAbleFieldsForResource (String $resource) : array
    {
        $key = 'search.meta.resource.' . $resource;
        if (\Cache::has($key))
            return \Cache::get($key, []);

        $modelFieldCacheMinutes = config('search.modelFieldCacheMinutes', 30);
        $underlyingModel = Utility::getModelFromResourceSlug($resource);
        $value = property_exists($underlyingModel, 'searchAble') ? $underlyingModel->searchAble : [];
        \Cache::put($key, $value, $modelFieldCacheMinutes);

        return $value;
    }

    public static function generateSearchKey (String $id) : string
    {
        return "search.instances." . $id;
    }
}