<?php


namespace App\Libraries;


use App\Constants\CRUDActions;
use App\Constants\UserRoles;
use App\Invoice;
use App\Node;
use App\NodeGroup;
use App\Order;
use App\User;
use Bouncer;

class PermissionManager
{
    public static function assign (User $user, String $role)
    {
        $resources = config('resources');
        $nodeResource = $resources['node'];
        $orderResource = $resources['order'];
        $invoiceResource = $resources['invoice'];
        $nodeGroupResource = $resources['node_group'];

        switch ($role)
        {
            case UserRoles::ADMIN:
            case UserRoles::STAFF:
                $user->assign($role);
                break;

            case UserRoles::USER:
                $user->assign($role);

                // ONLY SET TOOWN RULES HERE, GROUP RULES DO NOT GO HERE

                Bouncer::allow($user)
                    ->toOwn(Order::class)
                    ->to([
                        $orderResource . '.' . 'makeOrderDeliverable',
                        $orderResource . '.' . CRUDActions::DESTROY
                    ]);

                // Allow user to view/update/destroy THEIR OWN nodes
                Bouncer::allow($user)
                    ->toOwn(Node::class)
                    ->to([
                             $nodeResource . '.' . CRUDActions::SHOW,
                             $nodeResource . '.' . CRUDActions::UPDATE,
                             $nodeResource . '.' . CRUDActions::DESTROY,
                             $nodeResource . '.' . 'verify',
                             $nodeResource . '.' . 'assign'
                         ]);

                // Allow users to view THEIR OWN invoices and their PDF representations
                Bouncer::allow($user)
                    ->toOwn(Invoice::class)
                    ->to([
                             $invoiceResource . '.' . CRUDActions::SHOW,
                             $invoiceResource . '.' . 'render',
                             $invoiceResource . '.' . 'pay'
                         ]);

                // Allow users to view THEIR OWN invoices
                Bouncer::allow($user)
                    ->toOwn(Order::class)
                    ->to([
                             $orderResource . '.' . CRUDActions::SHOW,
                             $orderResource . '.' . 'subscribe'
                         ]);

                // Allow user to view/update/destroy THEIR OWN node groups
                Bouncer::allow($user)
                    ->toOwn(NodeGroup::class)
                    ->to([
                        $nodeGroupResource . '.' . CRUDActions::SHOW,
                        $nodeGroupResource . '.' . CRUDActions::UPDATE,
                        $nodeGroupResource . '.' . CRUDActions::DESTROY,
                        $nodeGroupResource . '.' . 'assign'
                    ]);
                break;

        }
    }
}