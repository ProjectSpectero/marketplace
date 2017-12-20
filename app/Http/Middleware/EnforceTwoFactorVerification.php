<?php

namespace App\Http\Middleware;

use Closure;
use App\UserMeta;
use App\Constants\UserMetaKeys;

class Verify
{
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
      
      $userVerified = UserMeta::loadMeta($request->user(), UserMetaKeys::Verified)->first();

      if ($userVerified->meta_value != 'true') {
        return response()->json(['error' => 'User not verified'], 401);
      } 

        // Post-Middleware Action

        return $next($request);
    }
}
