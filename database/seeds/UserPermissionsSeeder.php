<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Constants\CRUDActions;

class UserPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (CRUDActions::getConstants() as $permission   ) {
            Permission::create(['name' => $permission]);
        }
    }
}
