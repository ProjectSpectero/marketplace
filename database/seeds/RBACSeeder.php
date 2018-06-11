<?php

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
            ->to($invoiceResource . '.' . 'pdf');

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
    }
}
