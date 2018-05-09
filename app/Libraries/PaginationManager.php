<?php

namespace App\Libraries;

use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Errors\UserFriendlyException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;


class PaginationManager
{
    public static function paginate(Request $request, Builder $builder) : JsonResponse
    {
        /** @var \Illuminate\Validation\Validator $v */
        $v = Validator::make($request->all(), [
                                'page' => 'sometimes|integer',
                                'perPage' => 'sometimes|integer'
                           ]);

        if ($v->fails())
            throw new UserFriendlyException(Errors::REQUESTED_PAGE_DOES_NOT_EXIST);

        $requestedPage = $request->get('page', 1);

        /** @var LengthAwarePaginator $paginatedResource */
        $paginatedResource = static::internalPaginate($request, $builder)->toArray();

        if ($requestedPage > $paginatedResource['last_page'] && $requestedPage != 1)
            throw new UserFriendlyException(Errors::REQUESTED_PAGE_DOES_NOT_EXIST);

        $data = $paginatedResource['data'];
        unset($paginatedResource['data']);

        return Utility::generateResponse($data, [], null, 'v1', ResponseType::OK, [], $paginatedResource);
    }

    public static function internalPaginate (Request $request, Builder $builder)
    {
        $perPage = $request->get('perPage', config('pagination.default_per_page', 10));
        $maxPerPage = config('pagination.max_per_page', 15);

        $perPage = $perPage <= $maxPerPage ? $perPage : $maxPerPage;

        $paginated = $builder->paginate($perPage);
        $paginated->appends($request->query());

        return $paginated;
    }
}