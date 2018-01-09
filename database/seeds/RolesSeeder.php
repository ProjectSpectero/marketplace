<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Constants\CRUDActions;
use App\Constants\UserRoles;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userResource = config('resources')['user'];

        $admin = Role::create(['name' => UserRoles::ADMIN]);
        Role::create(['name' => UserRoles::USER])->givePermissionTo($userResource.'.'.CRUDActions::STORE);

        foreach (CRUDActions::getConstants() as $permission) {
            $admin->givePermissionTo($userResource.'.'.$permission);
        }
    }
}
