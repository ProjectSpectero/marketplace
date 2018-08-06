<?php

namespace App\Http\Middleware;

use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Errors\UserFriendlyException;
use App\Libraries\MultifactorVerifier;
use App\Libraries\Utility;
use Closure;
use Illuminate\Http\Request;

class EnforceTwoFactorVerification
{
    protected $version = 'v1';

    /**
     * Handle an incoming request.
     * This middleware NEEDS TO execute after the auth middleware, multifactor-less auth is available in the Auth | TwoFactor controllers
     * i.e: This middleware may only protect pages, but it cannot be used to log people in the first time if they have multifactor on
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     * @throws UserFriendlyException
     */
    public function handle(Request $request, Closure $next)
    {
        // Get the context values required
        $user = $request->user();

        if ($request->has('generatedToken'))
            $token = $request->get('generatedToken');
        elseif ($request->hasHeader('X-MULTIFACTOR-TOKEN'))
            $token = $request->header('X-MULTIFACTOR-TOKEN');
        else
            $token = null;

        // Endpoint protected by TFA, but we cannot proceed.
        if ($user == null || $token == null)
            throw new UserFriendlyException(Errors::MULTI_FACTOR_PARAMETERS_MISSING, ResponseType::UNPROCESSABLE_ENTITY);

        // Because there may be more than one header, we only care about the first.
        if (is_array($token))
            $token = $token[0];

        if (! MultifactorVerifier::verify($user, $token))
            throw new UserFriendlyException(Errors::MULTI_FACTOR_VERIFICATION_FAILED, ResponseType::FORBIDDEN);

        // Forward the request on, since the guy either didn't have TFA turned on, or passed it
        return $next($request);
    }
}
