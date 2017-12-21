<?php

namespace App\Http\Middleware;

use Closure;
use App\UserMeta;
use App\Repositories\UserRepository;
use App\Constants\UserMetaKeys;

class EnforceTwoFactorVerification
{

    protected $userRepository;    
    
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Pre-Middleware Action
        $verified = $this->userRepository->verifyUser($request->user(), $request->secret);        

        if (!$verified) {
            return response()->json(['temp error' => 'User not verifed']);
        }
           

        // Post-Middleware Action

        return $next($request);
    }
}
