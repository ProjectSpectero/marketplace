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
         $this->call(RBACSeeder::class);
         $this->call(UsersTableSeeder::class);
         $this->call(NodesTableSeeder::class);
         $this->call(BillingSeeder::class);
         $this->call(InitialEnterpriseSeeder::class);
    }
}
