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
        foreach ($resources as $resource)
        {
            foreach (CRUDActions::getConstants() as $permission)
            {
                $slug = $resource . '.' . $permission;
                $this->bouncer->allow(\App\Constants\UserRoles::ADMIN)->to($slug);
            }
        }

        $userResource = $resources['user'];
        $nodeResource = $resources['node'];

        // Allow normal users to create/index nodes
        $this->bouncer->allow(\App\Constants\UserRoles::USER)->to([
            $nodeResource . '.' . CRUDActions::STORE,
            $nodeResource . '.' . CRUDActions::INDEX
        ]);

        // Allow users to view/update/destroy THEIR OWN nodes
        $this->bouncer->allow(\App\Constants\UserRoles::USER)
            ->toOwn(\App\Node::class)
            ->to([
                     $nodeResource . '.' . CRUDActions::SHOW,
                     $nodeResource . '.' . CRUDActions::UPDATE,
                     $nodeResource . '.' . CRUDActions::DESTROY
             ]);
    }
}
