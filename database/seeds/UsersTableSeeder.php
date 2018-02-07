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

      $this->addMeta($admin);

      foreach (\App\Constants\UserStatus::getConstants() as $user)
      {
          $newUser = \App\User::create([
              'name' => 'Status ' . $user,
              'email' => $user . '@dev.com',
              'password' => \Illuminate\Support\Facades\Hash::make('temppass'),
              'status' => $user,
              'node_key' => \App\Libraries\Utility::getRandomString(2)
          ]);

          PermissionManager::assign($newUser, UserRoles::USER);
      }

      $activeUser = \App\User::where('email', '=', 'active@dev.com')->first();
      $this->addMeta($activeUser);

      // The admin is a normal user too, despite being an admin
      PermissionManager::assign($admin, UserRoles::ADMIN);
    }

    private function addMeta(\App\User $user)
    {
        try
        {
            UserMeta::addOrUpdateMeta($user,UserMetaKeys::AddressLineOne, env('LEGAL_COMPANY_ADDRESS_PARTIAL_1'));
            UserMeta::addOrUpdateMeta($user,UserMetaKeys::AddressLineTwo, env('LEGAL_COMPANY_ADDRESS_PARTIAL_2'));
            UserMeta::addOrUpdateMeta($user,UserMetaKeys::Organization, 'Spectero');
            UserMeta::addOrUpdateMeta($user, UserMetaKeys::TaxIdentification, 'taxId');
            UserMeta::addOrUpdateMeta($user, UserMetaKeys::PreferredCurrency, 'USD');
            UserMeta::addOrUpdateMeta($user, UserMetaKeys::City, 'Example City');
            UserMeta::addOrUpdateMeta($user, UserMetaKeys::State, 'Example State');
            UserMeta::addOrUpdateMeta($user, UserMetaKeys::Country, 'Example Country');
            UserMeta::addOrUpdateMeta($user, UserMetaKeys::PostCode, 1234);
        }
        catch (\App\Errors\FatalException $e)
        {

        }
    }
}
