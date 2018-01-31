<?php

use App\Constants\UserMetaKeys;
use App\Libraries\PermissionManager;
use App\UserMeta;
use Illuminate\Database\Seeder;
use App\Constants\UserRoles;

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

      foreach(App\User::all() as $user)
      {
          PermissionManager::assign($user, UserRoles::USER);
      }

      $admin = \App\User::create([
          'name' => "Spectero Dev",
          'email' => "spectero@dev.com",
          'password' => \Illuminate\Support\Facades\Hash::make('temppass'),
          'status' => \App\Constants\UserStatus::ACTIVE,
          'node_key' => \App\Libraries\Utility::getRandomString(2)
      ]);

      foreach (\App\Constants\UserStatus::getConstants() as $user)
      {
          \App\User::create([
              'name' => 'Status' . $user,
              'email' => $user . '@dev.com',
              'password' => \Illuminate\Support\Facades\Hash::make('temppass'),
              'status' => $user,
              'node_key' => \App\Libraries\Utility::getRandomString(2)
          ]);
      }

      try
      {
          UserMeta::addOrUpdateMeta($admin,UserMetaKeys::AddressLineOne, env('LEGAL_COMPANY_ADDRESS_PARTIAL_1'));
          UserMeta::addOrUpdateMeta($admin,UserMetaKeys::AddressLineTwo, env('LEGAL_COMPANY_ADDRESS_PARTIAL_2'));
          UserMeta::addOrUpdateMeta($admin,UserMetaKeys::Organization, 'Spectero');
          UserMeta::addOrUpdateMeta($admin, UserMetaKeys::TaxIdentification, 'taxId');
          UserMeta::addOrUpdateMeta($admin, UserMetaKeys::PreferredCurrency, 'USD');
      }
      catch (\App\Errors\FatalException $e)
      {

      }


      // The admin is a normal user too, despite being an admin
      PermissionManager::assign($admin, UserRoles::ADMIN);
    }
}
