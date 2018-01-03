<?php

namespace App\Http\Middleware;

use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Libraries\MultifactorVerifier;
use App\Libraries\Utility;
use Closure;
use Illuminate\Http\Request;

class EnforceTwoFactorVerification
{
    protected $version = "v1";
    /**
     * Handle an incoming request.
     * This middleware NEEDS TO execute after the auth middleware, multifactor-less auth is available in the Auth | TwoFactor controllers
     * i.e: This middleware may only protect pages, but it cannot be used to log people in the first time if they have multifactor on
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get the context values required
        $user = $request->user();
        $token = $request->get("generatedToken");

        if ($user == null || empty($token))
        {
            // BANISH HIM!
            return Utility::generateResponse(null, [ Errors::MULTI_FACTOR_PARAMETERS_MISSING => "" ], Errors::REQUEST_FAILED, $this->version, ResponseType::UNPROCESSABLE_ENTITY);
        }

        if (! MultifactorVerifier::verify($user, $token))
        {
            // Guy failed multifactor verification
            return Utility::generateResponse(null, [ Errors::MULTI_FACTOR_VERIFICATION_FAILED => "" ], Errors::REQUEST_FAILED, $this->version, ResponseType::FORBIDDEN);
        }

        // Forward the request on, since the guy either didn't have TFA turned on, or passed it
        return $next($request);
    }
}
