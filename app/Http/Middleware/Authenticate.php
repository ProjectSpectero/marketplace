<?php

namespace App\Http\Middleware;

use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Errors\UserFriendlyException;
use App\Libraries\Utility;
use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  string|null $guard
     * @return mixed
     * @throws UserFriendlyException
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if ($this->auth->guard($guard)->guest())
            throw new UserFriendlyException(Errors::UNAUTHORIZED, ResponseType::NOT_AUTHORIZED);

        $error = Utility::resolveStatusError($request->user());
        if (! empty($error))
            return Utility::generateResponse(null, [ $error ], Errors::REQUEST_FAILED, 'v1',
                                                    ResponseType::NOT_AUTHORIZED);

        return $next($request);
    }
}
