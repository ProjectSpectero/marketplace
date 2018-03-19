<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\Events;
use App\Constants\Messages;
use App\Constants\ResponseType;
use App\Events\UserEvent;
use App\Http\Controllers\V1\V1Controller;
use App\Libraries\Utility;
use App\Mail\PasswordChanged;
use App\Mail\PasswordReset;
use App\PasswordResetToken;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends V1Controller
{
    // This is the one that looks up an email address and emails them a reset link
    public function generateToken (Request $request) : JsonResponse
    {
        // Remember to prevent user enumeration. At no point should we confirm or deny a specific email/user exists
        // Just say that IF a user exists with the email address you've shared, they'll receive an email.
        // Create a PasswordResetToken object accordingly, set expiry to now + env('PASSWORD_RESET_TOKEN_EXPIRY')
        $rules = [
            'email' => 'required|email'
        ];

        $this->validate($request, $rules);
        $email = $request->get('email');

        try
        {
            $user = User::where('email', '=', $email)->firstOrFail();
            $ip = $request->ip();

            $resetToken = PasswordResetToken::create([
                'token' => Utility::getRandomString(2),
                'user_id' => $user->id,
                'ip' => $ip,
                'expires' => Carbon::now()->addMinutes(env('PASSWORD_RESET_TOKEN_EXPIRY', 60))
            ]);

            Mail::to($email)->queue(new PasswordReset($resetToken, $ip));
        }
        catch (ModelNotFoundException $silenced)
        {
            // Intentionally silenced to prevent email enumeration
        }

        // This is NOT a success message, but a generic acknowledgement instead. It should be made clear to the user
        // that they will ONLY receive an email if they have an account with us. Otherwise nothing.

        return $this->respond(null, [], Messages::PASSWORD_RESET_TOKEN_ISSUED);
    }

    /**
     * @param Request $request
     * @param String $token
     * @return JsonResponse
     */
    public function callback (Request $request, String $token) : JsonResponse
    {
        try
        {
            $resetToken = PasswordResetToken::where('token', '=', $token)->firstOrFail();
        }
        catch (ModelNotFoundException $e)
        {
            return $this->respond(null, [ Errors::RESOURCE_NOT_FOUND ], ResponseType::NOT_FOUND);
        }

        $user = $resetToken->user;
        $newPassword = Utility::getRandomString();
        $user->password = Hash::make($newPassword);
        $user->save();

        Mail::to($user->email)->queue(new PasswordChanged($newPassword, $resetToken->ip));

        event(new UserEvent(Events::USER_PASSWORD_UPDATED, $user));
        return $this->respond(null, [], Messages::PASSWORD_RESET_SUCCESS);
    }
}
