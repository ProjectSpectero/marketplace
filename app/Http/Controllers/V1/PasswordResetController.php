<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\Messages;
use App\Constants\ResponseType;
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
        // TODO: implement this, remember to prevent user enumeration. At no point should we confirm or deny a specific email/user exists
        // Just say that IF a user exists with the email address you've shared, they'll receive an email.
        // Create a PasswordResetToken object accordingly, set expiry to now + env('PASSWORD_RESET_TOKEN_EXPIRY')
        $email = $request->get('email');

        try
        {
            $user = User::where('email', '=', $email)->firstOrFail();
            $resetToken = PasswordResetToken::create([
                'token' => Utility::getRandomString(),
                'user_id' => $user->id,
                'expires' => Carbon::now()->addMinutes(env('PASSWORD_RESET_TOKEN_EXPIRY'))
            ]);
            Mail::to($email)->queue(new PasswordReset($resetToken, $request->ip()));

            return $this->respond(null, [], Messages::PASSWORD_RESET_TOKEN_ISSUED);
        }
        catch (ModelNotFoundException $e)
        {
            return $this->respond(null, [], Messages::PASSWORD_RESET_TOKEN_ISSUED);
        }
    }

    // This is the one that emailed link corresponds to.
    public function callback (Request $request, String $token) : JsonResponse
    {
        // TODO: implement this

        try
        {
            $resetToken = PasswordResetToken::where('token', '=', $token)->firstOrFail();
            $user = $resetToken->user;
            $newPassword = Utility::getRandomString();
            $user->password = Hash::make($newPassword);
            $user->save();

            Mail::to($user->email)->queue(new PasswordChanged($newPassword));

            return $this->respond(null, [], Messages::PASSWORD_RESET_SUCCESS);
        }
        catch (ModelNotFoundException $e)
        {
            return $this->respond(null, Errors::RESOURCE_NOT_FOUND, ResponseType::NOT_FOUND);
        }
    }
}
