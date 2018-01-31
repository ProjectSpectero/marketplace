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
        // TODO: actually take care of all this.
        // TODO: Created/Updated should take care of email verification

        $user = $event->user;
        $dataBag = $event->dataBag;

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

                /** @var User $oldUser */
                $oldUser = isset($dataBag['previous']) ? $dataBag['previous'] : null;
                if ($oldUser != null && $oldUser->email != $user->email)
                {
                    $user->status = UserStatus::EMAIL_VERIFICATION_NEEDED;
                    $user->saveOrFail();

                    $oldEmail = UserMeta::loadMeta($user, UserMetaKeys::OldEmailAddress);

                    Mail::to($user->email)->queue(new EmailChangeNew());
                    Mail::to($oldEmail)->queue(new EmailChangeOld());

                    // Keep an audit trail to assist people who had their accounts taken over.
                    \Log::info(sprintf("User id: %d had its email changed from: %s to: %s\n", $user->id, $oldUser->email, $user->email));
                }
                break;
            case Events::USER_DELETED:
                break;

            case Events::USER_PASSWORD_UPDATED:
                break;
        }
    }
}
