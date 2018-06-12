<?php

use App\Invoice;
use App\Node;
use App\NodeGroup;
use App\Order;
use Illuminate\Database\Seeder;
use App\Constants\CRUDActions;

class RBACSeeder extends Seeder
{
    private $bouncer;
    public function __construct(Silber\Bouncer\Bouncer $bouncer)
    {
        $this->bouncer = $bouncer;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $resources = config('resources');
        $userResource = $resources['user'];
        $nodeResource = $resources['node'];
        $nodeGroupResource = $resources['node_group'];
        $orderResource = $resources['order'];
        $invoiceResource = $resources['invoice'];

        foreach ($resources as $resource)
        {
            foreach (CRUDActions::getConstants() as $permission)
            {
                $slug = $resource . '.' . $permission;
                $this->bouncer->allow(\App\Constants\UserRoles::STAFF)->to($slug);
            }
        }

        // Add non-CRUD roles to the admin class
        $this->bouncer->allow(\App\Constants\UserRoles::STAFF)
            ->to([
                     $invoiceResource . '.' . 'pdf',
                     $nodeResource . '.' . 'verify',
                     'manual.pay'
            ]);

        $this->bouncer->allow(\App\Constants\UserRoles::STAFF)
            ->to($nodeResource . '.' . 'verify');

        // Define generic, role-specific permissions here on a per resource basis

        // Allow normal users to create nodes
        $this->bouncer->allow(\App\Constants\UserRoles::USER)
            ->to([
                 // Allow user to create nodes
                     $nodeResource . '.' . CRUDActions::STORE,
                 // Allow user to create node groups
                     $nodeGroupResource . '.' . CRUDActions::STORE,
                 // Allow user to create cart based orders
                     $orderResource . '.' . 'cart'
             ]);

        // Authorizations for resources owned by specific users.

        Bouncer::allow(\App\Constants\UserRoles::USER)
            ->toOwn(Order::class)
            ->to([
                     $orderResource . '.' . 'makeOrderDeliverable',
                     $orderResource . '.' . CRUDActions::SHOW,
                     $orderResource . '.' . 'subscribe',
                     $orderResource . '.' . CRUDActions::DESTROY
                 ]);

        // Allow user to view/update/destroy THEIR OWN nodes
        Bouncer::allow(\App\Constants\UserRoles::USER)
            ->toOwn(Node::class)
            ->to([
                     $nodeResource . '.' . CRUDActions::SHOW,
                     $nodeResource . '.' . CRUDActions::UPDATE,
                     $nodeResource . '.' . CRUDActions::DESTROY,
                     $nodeResource . '.' . 'verify',
                     $nodeResource . '.' . 'assign'
                 ]);

        // Allow users to view THEIR OWN invoices and their PDF representations
        Bouncer::allow(\App\Constants\UserRoles::USER)
            ->toOwn(Invoice::class)
            ->to([
                     $invoiceResource . '.' . CRUDActions::SHOW,
                     $invoiceResource . '.' . 'render',
                     $invoiceResource . '.' . 'pay'
                 ]);

        // Allow user to view/update/destroy THEIR OWN node groups
        Bouncer::allow(\App\Constants\UserRoles::USER)
            ->toOwn(NodeGroup::class)
            ->to([
                     $nodeGroupResource . '.' . CRUDActions::SHOW,
                     $nodeGroupResource . '.' . CRUDActions::UPDATE,
                     $nodeGroupResource . '.' . CRUDActions::DESTROY,
                     $nodeGroupResource . '.' . 'assign'
                 ]);
    }
}
