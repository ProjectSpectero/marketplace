<?php


namespace App\Libraries;


use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Errors\UserFriendlyException;
use App\Models\Opaque\SearchEntity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class SearchManager
{
    /**
     * @param Request $request
     * @param String $caller
     * @param Builder|null $sourceBuilder
     * @return Builder
     * @throws UserFriendlyException
     */
    public static function process (Request $request, String $caller, Builder $sourceBuilder = null) : Builder
    {
        $table = null;
        if ($sourceBuilder == null)
        {
            $model = Utility::getModelFromResourceSlug($caller);
            $table = $model->getTable();
        }
        else
        {
            $model = $sourceBuilder;
            $table = $sourceBuilder->newModelInstance()->getTable();
        }

        if ($request->has('searchId'))
        {
            $searchId = $request->get('searchId');
            /** @var SearchEntity $searchEntity */
            $key = self::generateSearchKey($searchId);
            $searchEntity = \Cache::has($key) ? \Cache::get($key) : null;

            if ($searchEntity == null)
                throw new UserFriendlyException(Errors::SEARCH_ID_INVALID_OR_EXPIRED, ResponseType::UNPROCESSABLE_ENTITY);

            if ($searchEntity->resource !== $caller)
                throw new UserFriendlyException(Errors::SEARCH_RESOURCE_MISMATCH);

            $constraints = [];
            $sortParams = null;
            foreach ($searchEntity->rules as $rule)
            {
                switch ($rule['operator'])
                {
                    case 'LIKE':
                        $rule['value'] = '%' . $rule['value'] . '%';
                        break;

                    case 'SORT':
                        if ($sortParams != null)
                            continue 2;

                        if (! in_array($rule['value'], [ 'ASC', 'DESC' ]))
                            $rule['value'] = 'ASC';

                        $sortParams = [
                            'field' => $rule['field'],
                            'value' => $rule['value']
                        ];
                        continue 2;
                        break;
                }

                $constraints[] = [ $table . '.' . $rule['field'], $rule['operator'], $rule['value'] ];
            }

            $queryBuilder = $model->where($constraints);
            $intuitiveIdParameter = sprintf('%s.id', $table);

            return $sortParams == null ? $queryBuilder->orderBy($intuitiveIdParameter, 'ASC') : $queryBuilder->orderBy($sortParams['field'], $sortParams['value']);
        }

        return $model->newQuery();
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