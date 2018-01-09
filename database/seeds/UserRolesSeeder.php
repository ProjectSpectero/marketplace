<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Constants\CRUDActions;

class UserRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userResource = config('resources')['user'];

        $admin = Role::create(['name' => 'admin']);
        Role::create(['name' => 'user'])->givePermissionTo($userResource.'.'.CRUDActions::STORE);

        foreach (CRUDActions::getConstants() as $permission) {
            $admin->givePermissionTo($userResource.'.'.$permission);
        }
    }
}
