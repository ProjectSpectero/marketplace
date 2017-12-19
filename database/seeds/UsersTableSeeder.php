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

      \App\User::create([
        "name" => "Spectero Dev",
        "email" => "spectero@dev.com",
        "password" => "$2y$10slEBapxniAtsDj85a5f1i.QMecJI.2eJbzREuLoxes1yQ5Q7RL1Da" 
      ]);
    }
}
