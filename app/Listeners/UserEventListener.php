<?php

namespace App\Listeners;

use App\Constants\Events;
use App\Constants\UserMetaKeys;
use App\Constants\UserStatus;
use App\Events\UserEvent;
use App\Libraries\Utility;
use App\Mail\EmailChangeNew;
use App\Mail\EmailChangeOld;
use App\Mail\PasswordChanged;
use App\Mail\WelcomeWithEmailValidation;
use App\Mail\Welcome;
use App\User;
use App\UserMeta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use phpDocumentor\Reflection\Types\Self_;

class UserEventListener extends BaseListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    private function generateVerifyToken (User $user)
    {
        $stringToken = Utility::getRandomString();
        $token = [
            'token' => $stringToken,
            'email' => $user->email
        ];

        $verifyToken = json_encode($token);

        UserMeta::addOrUpdateMeta($user, UserMetaKeys::VerifyToken, $verifyToken);

        return $stringToken;
    }

    /**
     * Handle the event.
     *
     * @param  "UserEvent"  $event
     * @return void
     */
    public function handle(UserEvent $event)
    {
        $user = $event->user;

        /** @var User $oldUser */
        $oldUser = Utility::getPreviousModel($event->dataBag);
        $error = Utility::getError($event->dataBag);

        switch ($event->type)
        {
            case Events::USER_CREATED:
                $easy = $event->dataBag['easy'] ?? false;
                $resetToken = $event->dataBag['resetToken'] ?? null;

                if ($user->status == UserStatus::EMAIL_VERIFICATION_NEEDED)
                {
                    if ($easy)
                    {
                        $params = [
                            'for' => $user->email,
                            'easy' => true
                        ];

                        $queryString = http_build_query($params);

                        $url = Utility::generateUrl("password-reset/$resetToken?$queryString", 'frontend');

                    }
                    else
                        $url = Utility::generateUrl('verify/' . $user->email . '/' . $this->generateVerifyToken($user), 'frontend');

                    $mail = new WelcomeWithEmailValidation($user, $url, $easy);
                }
                else
                    $mail = new Welcome();

                Mail::to($user->email)
                    ->queue($mail);

                break;
            case Events::USER_UPDATED:
                $oldEmailHolder = UserMeta::loadMeta($user, UserMetaKeys::OldEmailAddress);
                if ($oldEmailHolder != null && ! $oldEmailHolder instanceof Builder)
                    $oldEmail = $oldEmailHolder->meta_value;
                else
                    $oldEmail = null;

                if ($oldEmail != null && $oldEmail !== $user->email)
                {
                    $user->status = UserStatus::EMAIL_VERIFICATION_NEEDED;
                    $user->saveOrFail();
                    Mail::to($oldEmail)->queue(new EmailChangeOld($user->email));

                    $verifyToken = $this->generateVerifyToken($user);

                    // Keep an audit trail to assist people who had their accounts taken over.
                    \Log::info(sprintf("User id: %d had its email changed from: %s to: %s\n", $user->id, $oldEmail, $user->email));

                    // Do this regardless, have them verify the new email
                    Mail::to($user->email)->queue(new EmailChangeNew($user, $verifyToken));

                    // Now, we need to remove all oAuth tokens they might have issued. This will log them out from every device.
                    self::cleanUpUserAuthTokens($user->id);
                }

                break;
            case Events::USER_DELETED:
                break;

            case Events::USER_PASSWORD_UPDATED:
                // Let's notify the user that their password has been changed.
                Mail::to($user->email)->queue(new PasswordChanged('undisclosed', $event->dataBag['ip']));

                // Now, we need to remove all oAuth tokens they might have issued. This will log them out from every device.
                self::cleanUpUserAuthTokens($user->id);

                \Log::info(sprintf("User id: %d had their password updated.", $user->id));

                break;
        }
    }

    private static function cleanUpUserAuthTokens (int $id)
    {
        DB::table('oauth_access_tokens')
            ->where('user_id', '=', $id)
            ->delete();
    }
}
