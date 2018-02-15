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
      PermissionManager::assign($admin, UserRoles::ADMIN);

      foreach (\App\Constants\UserStatus::getConstants() as $user)
      {
          switch ($user)
          {
              case \App\Constants\UserStatus::ACTIVE:
                  for ($i = 0; $i < 2; $i++)
                  {
                      $newUser = \App\User::create([
                                                       'name' => 'Status ' . $user . ' ' . $i,
                                                       'email' => $user . '-' . $i . '@dev.com',
                                                       'password' => \Illuminate\Support\Facades\Hash::make('temppass'),
                                                       'status' => $user,
                                                       'node_key' => \App\Libraries\Utility::getRandomString(2)
                                                   ]);
                      PermissionManager::assign($newUser, UserRoles::USER);
                      $this->addMeta($newUser);
                  }
                  break;

              default:
                  $newUser = \App\User::create([
                                                   'name' => 'Status ' . $user ,
                                                   'email' => $user . '@dev.com',
                                                   'password' => \Illuminate\Support\Facades\Hash::make('temppass'),
                                                   'status' => $user,
                                                   'node_key' => \App\Libraries\Utility::getRandomString(2)
                                               ]);
                  PermissionManager::assign($newUser, UserRoles::USER);
          }

          PermissionManager::assign($newUser, UserRoles::USER);
      }
    }

    private function addMeta(\App\User $user)
    {
        try
        {
            UserMeta::addOrUpdateMeta($user,UserMetaKeys::AddressLineOne, env('LEGAL_COMPANY_ADDRESS_PARTIAL_1'));
            UserMeta::addOrUpdateMeta($user,UserMetaKeys::AddressLineTwo, env('LEGAL_COMPANY_ADDRESS_PARTIAL_2'));
            UserMeta::addOrUpdateMeta($user,UserMetaKeys::Organization, 'Spectero');
            UserMeta::addOrUpdateMeta($user, UserMetaKeys::TaxIdentification, 'FRC-3-612A1521');
            UserMeta::addOrUpdateMeta($user, UserMetaKeys::PreferredCurrency, 'USD');
            UserMeta::addOrUpdateMeta($user, UserMetaKeys::City, 'Shinagawa-ku');
            UserMeta::addOrUpdateMeta($user, UserMetaKeys::State, 'Tokyo');
            UserMeta::addOrUpdateMeta($user, UserMetaKeys::Country, 'JP');
            UserMeta::addOrUpdateMeta($user, UserMetaKeys::PostCode, 68005);
        }
        catch (\App\Errors\FatalException $e)
        {

        }
    }
}
