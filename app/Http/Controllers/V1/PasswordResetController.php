<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\Events;
use App\Constants\Messages;
use App\Constants\ResponseType;
use App\Constants\UserMetaKeys;
use App\Constants\UserStatus;
use App\Errors\UserFriendlyException;
use App\Events\UserEvent;
use App\Http\Controllers\V1\V1Controller;
use App\Libraries\Utility;
use App\Mail\PasswordChanged;
use App\Mail\PasswordReset;
use App\PasswordResetToken;
use App\User;
use App\UserMeta;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends V1Controller
{
    // This is the one that looks up an email address and emails them a reset link
    public function generate (Request $request) : JsonResponse
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

            // Cleanup old tokens (if any exist)
            PasswordResetToken::where('user_id', $user->id)
                ->delete();

            $resetToken = PasswordResetToken::create([
                'token' => Utility::getRandomString(2),
                'user_id' => $user->id,
                'ip' => $ip,
                'expires' => Carbon::now()->addMinutes(env('PASSWORD_RESET_TOKEN_EXPIRY', 60))->diffInM
            ]);

            Mail::to($email)->queue(new PasswordReset($resetToken, $ip));
        }
        catch (ModelNotFoundException $silenced)
        {
            // Intentionally silenced to prevent email enumeration
            \Log::warning("A password reset was attempted for $email from $ip, but no users could be found! User enumeration is being attempted if this message repeats too many times.");
        }

        // This is NOT a success message, but a generic acknowledgement instead. It should be made clear to the user
        // that they will ONLY receive an email if they have an account with us. Otherwise nothing.

        return $this->respond(null, [], Messages::PASSWORD_RESET_TOKEN_ISSUED);
    }

    public function show (Request $request, string $token) : JsonResponse
    {
        $resetToken = PasswordResetToken::where('token', '=', $token)->firstOrFail();

        if ($request->ip() !== $resetToken->ip)
            throw new UserFriendlyException(Errors::IP_ADDRESS_MISMATCH, ResponseType::FORBIDDEN);

        return $this->respond($resetToken->toArray());
    }

    public function reset(Request $request, string $token)
    {
        $rules = [
            'password' => 'sometimes|min:5|max:72'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        $resetToken = PasswordResetToken::where('token', '=', $token)->firstOrFail();

        if ($request->ip() !== $resetToken->ip)
            throw new UserFriendlyException(Errors::IP_ADDRESS_MISMATCH, ResponseType::FORBIDDEN);

        $user = $resetToken->user;
        $newPassword = $input['password'] ?? Utility::getRandomString();
        $user->password = Hash::make($newPassword);
        $user->save();

        if ($user->status == UserStatus::EMAIL_VERIFICATION_NEEDED)
        {
            $wasUserEasySignedUp = UserMeta::cacheLoad($user, UserMetaKeys::SourcedFromEasySignup);
            if ($wasUserEasySignedUp)
            {
                UserMeta::deleteMeta($user, UserMetaKeys::SourcedFromEasySignup);
                $user->status = UserStatus::ACTIVE;

                $user->saveOrFail();
            }
        }

        event(new UserEvent(Events::USER_PASSWORD_UPDATED, $user, [
            'ip' => $resetToken->ip
        ]));

        $resetToken->delete();

        return $this->respond([
                                  'new_password' => $newPassword
                              ], [], Messages::PASSWORD_RESET_SUCCESS);
    }
}
