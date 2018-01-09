<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      factory(App\User::class, 5)->create();

      foreach(App\User::all() as $user) {
          $user->assignRole('user');
      }

      \App\User::create([
        "name" => "Spectero Dev",
        "email" => "spectero@dev.com",
        "password" => \Illuminate\Support\Facades\Hash::make('temppass') 
      ])->assignRole('admin');
    }
}
