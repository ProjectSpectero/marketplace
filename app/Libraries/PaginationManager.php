<?php

namespace App\Libraries;

use App\Libraries\Utility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\Types\Integer;


class PaginationManager
{
    public static function paginate(String $resource, $perPage)
    {
        $maxPerPage = config('pagination.max_per_page');

        $resourceName = in_array($resource, config('resources')) ? $resource : '';
        $perPage = $perPage <= $maxPerPage ? $perPage : $maxPerPage;

        $model = Utility::getModelFromResourceSlug($resourceName)->firstOrFail();

        return $model::paginate($perPage);
    }
}