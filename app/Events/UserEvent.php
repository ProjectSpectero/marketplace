<?php

namespace App\Events;

use App\User;

class UserEvent extends Event
{
    public $user;

    /**
     * Create a new event instance.
     *
     * @param String $type
     * @param User $user
     * @param array $dataBag
     */
    public function __construct(String $type, User $user,  array $dataBag = [])
    {
        $this->user = $user;
        $this->type = $type;
        $this->dataBag = $dataBag;
    }
}
