<?php

namespace App\Libraries;

use App\Constants\ResponseType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class PaginationManager
{
    public static function paginate(Request $request, Builder $builder) : JsonResponse
    {
        $perPage = $request->get('perPage', config('pagination.default_per_page'));
        $maxPerPage = config('pagination.max_per_page');

        $perPage = $perPage <= $maxPerPage ? $perPage : $maxPerPage;

        $paginated = $builder->paginate($perPage);
        $paginatedResource = $paginated->toArray();
        $data = $paginatedResource['data'];
        unset($paginatedResource['data']);

        return Utility::generateResponse($data, [], null, 'v1', ResponseType::OK, [], $paginatedResource);
    }
}