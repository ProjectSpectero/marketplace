<?php


namespace App\Http\Middleware;


use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Errors\UserFriendlyException;
use Closure;
use Illuminate\Http\Request;
use ReCaptcha\ReCaptcha;

class EnforceCaptcha
{
    private $recaptcha;

    public function __construct()
    {
        if (env('CAPTCHA_ENABLED', false))
            $this->recaptcha = new ReCaptcha(env('CAPTCHA_SECRET_KEY', ""));
    }

    public function handle(Request $request, Closure $next)
    {
        if (env('CAPTCHA_ENABLED', false))
        {
            // Get the context values required
            $providedChallengeResponse = $request->header('X-CAPTCHA-RESPONSE', "");
            $requesterIp = $request->ip();

            if (empty($providedChallengeResponse))
                throw new UserFriendlyException(Errors::CAPTCHA_MISSING);

            $response = $this->recaptcha->verify($providedChallengeResponse, $requesterIp);

            if (! $response->isSuccess())
                throw new UserFriendlyException(Errors::CAPTCHA_INVALID, ResponseType::FORBIDDEN);
        }

        // Forward the request on, captcha was passed.
        return $next($request);
    }
}