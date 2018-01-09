<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
         $this->call(UserPermissionsSeeder::class);
         $this->call(UserRolesSeeder::class);
         $this->call(UsersTableSeeder::class);
    }
}
