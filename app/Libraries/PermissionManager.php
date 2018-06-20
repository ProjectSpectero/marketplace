<?php


namespace App\Libraries;

use App\Constants\UserRoles;
use App\User;


class PermissionManager
{
    public static function assign (User $user, String $role)
    {
        $resources = config('resources');
        $nodeResource = $resources['node'];
        $orderResource = $resources['order'];
        $invoiceResource = $resources['invoice'];
        $nodeGroupResource = $resources['node_group'];

        // There isn't really any specific handling for this stuff quite yet, perhaps once we have some.
        // Only use this to add abilities that don't make sense to be attached to roles instead. For everything else, please attach directly to the roles instead.

        switch ($role)
        {
            case UserRoles::ADMIN:
            case UserRoles::STAFF:
            case UserRoles::USER:
                $user->assign($role);
                break;

        }
    }
}