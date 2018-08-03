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
        $this->recaptcha = new ReCaptcha(env('G_RECAPTCHA_SECRET_KEY', ""));
    }

    public function handle(Request $request, Closure $next)
    {
        // Get the context values required
        $providedChallengeResponse = $request->header('X-CAPTCHA-RESPONSE', "");
        $requesterIp = $request->ip();

        if (empty($providedChallengeResponse))
            throw new UserFriendlyException(Errors::CAPTCHA_MISSING);

        $response = $this->recaptcha->verify($providedChallengeResponse, $requesterIp);

        if (! $response->isSuccess())
            throw new UserFriendlyException(Errors::CAPTCHA_INVALID, ResponseType::FORBIDDEN);

        // Forward the request on, since the guy either didn't have TFA turned on, or passed it
        return $next($request);
    }
}