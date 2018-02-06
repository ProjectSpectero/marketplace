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

                $verifyToken = Utility::getRandomString();

                UserMeta::addOrUpdateMeta($user, UserMetaKeys::VerifyToken, $verifyToken);

                Mail::to($user->email)
                    ->queue(new $class($user, $verifyToken));

                break;
            case Events::USER_UPDATED:
                $oldEmail = UserMeta::loadMeta($user, UserMetaKeys::OldEmailAddress);
                if ($oldEmail != null && $oldEmail !== $user->email)
                {
                    $user->status = UserStatus::EMAIL_VERIFICATION_NEEDED;
                    $user->saveOrFail();
                    Mail::to($oldEmail)->queue(new EmailChangeOld());

                    // Keep an audit trail to assist people who had their accounts taken over.
                    \Log::info(sprintf("User id: %d had its email changed from: %s to: %s\n", $user->id, $oldEmail, $user->email));

                    // Do this regardless, have them verify the new email
                    Mail::to($user->email)->queue(new EmailChangeNew());
                }

                break;
            case Events::USER_DELETED:
                break;

            case Events::USER_PASSWORD_UPDATED:
                break;
        }
    }
}
