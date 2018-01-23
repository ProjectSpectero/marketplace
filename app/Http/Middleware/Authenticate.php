<?php

namespace App\Http\Middleware;

use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Constants\UserStatus;
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
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if ($this->auth->guard($guard)->guest())
            return Utility::generateResponse(null, [ Errors::UNAUTHORIZED ], null, 'v1', ResponseType::NOT_AUTHORIZED);

        if ($request->user()->status !== UserStatus::ACTIVE)
            return Utility::generateResponse(null, [ Errors::AUTHENTICATION_NOT_ALLOWED . ':' . $request->user()->status ],
                                             null, 'v1', ResponseType::FORBIDDEN);

        return $next($request);
    }
}
