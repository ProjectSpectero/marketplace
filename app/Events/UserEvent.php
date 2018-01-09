<?php

namespace App\Events;

use App\User;

class UserEvent extends Event
{
    public $user;
    public $type;

    /**
     * Create a new event instance.
     *
     * @param String $type
     * @param User $user
     */
    public function __construct(String $type, User $user)
    {
        $this->user = $user;
        $this->type = $type;
    }
}
