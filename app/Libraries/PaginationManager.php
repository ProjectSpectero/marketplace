<?php

namespace App\Libraries;

use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Errors\UserFriendlyException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class PaginationManager
{
    public static function paginate(Request $request, Builder $builder) : JsonResponse
    {
        $requestedPage = $request->get('page', 1);
        $perPage = $request->get('perPage', config('pagination.default_per_page'));
        $maxPerPage = config('pagination.max_per_page');

        $perPage = $perPage <= $maxPerPage ? $perPage : $maxPerPage;

        $paginated = $builder->paginate($perPage);
        $paginated->appends($request->query());
        $paginatedResource = $paginated->toArray();

        if ($requestedPage > $paginatedResource['last_page'])
            throw new UserFriendlyException(Errors::REQUESTED_PAGE_DOES_NOT_EXIST);

        $data = $paginatedResource['data'];
        unset($paginatedResource['data']);

        return Utility::generateResponse($data, [], null, 'v1', ResponseType::OK, [], $paginatedResource);
    }
}