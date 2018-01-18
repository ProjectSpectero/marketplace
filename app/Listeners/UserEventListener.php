<?php

namespace App\Listeners;

use App\Constants\Events;
use App\Constants\UserStatus;
use App\Events\UserEvent;
use App\User;

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
                // TODO: Send the user a welcome email accordingly
                // Save the verify token in their meta key, and define a route that takes their user_id and this token to verify them in UserController
                // Clean the token up once done (in the controller verification method)
                /*
                 * Here's how to send mail
                 * Mail::to($user->email)
                 *  ->queue(new Welcome()); <-- or WelcomeWithEmailValidation, these views need to be built, they're blank now.
                 */
                break;
            case Events::USER_UPDATED:

                /** @var User $oldUser */
                $oldUser = isset($dataBag['previous']) ? $dataBag['previous'] : null;
                if ($oldUser != null && $oldUser->email != $user->email)
                {
                    $user->status = UserStatus::EMAIL_VERIFICATION_NEEDED;
                    $user->saveOrFail();
                    // TODO: Send the user a mail at their NEW (user->email) address requesting that it be verified
                    // TODO: Send the user a mail at their OLD (oldUser->email) address notifying that email has been changed, and that they should contact us if this wasn't them.

                    // Keep an audit trail to assist people who had their accounts taken over.
                    \Log::info(sprintf("User id: %d had its email changed from: %s to: %s\n", $user->id, $oldUser->email, $user->email));
                }
                break;
            case Events::USER_DELETED:
                break;
        }
    }
}
