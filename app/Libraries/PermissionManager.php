<?php


namespace App\Libraries;


use App\Constants\CRUDActions;
use App\Constants\UserRoles;
use App\Invoice;
use App\Node;
use App\Order;
use App\User;
use Bouncer;

class PermissionManager
{
    public static function assign (User $user, String $role)
    {
        $resources = config('resources');
        $userResource = $resources['user'];
        $nodeResource = $resources['node'];
        $orderResource = $resources['order'];
        $invoiceResource = $resources['invoice'];

        switch ($role)
        {
            case UserRoles::ADMIN:
            case UserRoles::STAFF:
                $user->assign($role);
                break;

            case UserRoles::USER:
                $user->assign($role);
                // Allow user to view/update/destroy THEIR OWN nodes
                Bouncer::allow($user)
                    ->toOwn(Node::class)
                    ->to([
                             $nodeResource . '.' . CRUDActions::SHOW,
                             $nodeResource . '.' . CRUDActions::UPDATE,
                             $nodeResource . '.' . CRUDActions::DESTROY,
                             $nodeResource . '.' . 'verify'
                         ]);

                // Allow users to view THEIR OWN invoices and their PDF representations
                Bouncer::allow($user)
                    ->toOwn(Invoice::class)
                    ->to([
                             $invoiceResource . '.' . CRUDActions::SHOW,
                             $invoiceResource . '.' . 'render'
                         ]);

                // Allow users to create/view THEIR OWN orders
                Bouncer::allow($user)
                    ->to([
                        $orderResource . '.' . CRUDActions::STORE
                    ]);

                // Allow users to view THEIR OWN invoices
                Bouncer::allow($user)
                    ->toOwn(Order::class)
                    ->to([
                             $orderResource . '.' . CRUDActions::SHOW
                         ]);
                break;

        }
    }
}