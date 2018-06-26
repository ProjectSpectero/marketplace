<?php

namespace App\Listeners;

use App\Constants\Events;
use App\Constants\UserMetaKeys;
use App\Constants\UserStatus;
use App\Events\UserEvent;
use App\Libraries\Utility;
use App\Mail\EmailChangeNew;
use App\Mail\EmailChangeOld;
use App\Mail\WelcomeWithEmailValidation;
use App\Mail\Welcome;
use App\User;
use App\UserMeta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

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
                $template = $user->status == UserStatus::EMAIL_VERIFICATION_NEEDED ? 'WelcomeWithEmailValidation' : 'Welcome';
                $class = "\App\Mail\\" . $template;

                $verifyToken = $this->generateVerifyToken($user);

                Mail::to($user->email)
                    ->queue(new $class($user, $verifyToken));

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
                }

                break;
            case Events::USER_DELETED:
                break;

            case Events::USER_PASSWORD_UPDATED:
                // TODO: Notify the user that their password has been changed, and that they should immediately contact support if it was not them.
                break;
        }
    }
}
