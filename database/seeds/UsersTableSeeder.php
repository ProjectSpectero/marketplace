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
          'email' => "dev@spectero.com",
          'password' => \Illuminate\Support\Facades\Hash::make('temppass'),
          'status' => \App\Constants\UserStatus::ACTIVE,
          'node_key' => '25e7e751047aad89f9fd7fa19fe806618ee9e944cbeb861398f8e4534498659a'
        ]);

        $this->addMeta($admin);
        PermissionManager::assign($admin, UserRoles::ADMIN);

        $anatolie = \App\User::create([
                                          'name' => "Anatolie Diordita",
                                          'email' => 'anatolie@spectero.com',
                                          'password' => \Illuminate\Support\Facades\Hash::make('temppass'),
                                          'status' => \App\Constants\UserStatus::ACTIVE,
                                          'node_key' => 'f57a42d7efa1ed4071e2d1ab5cd39bc7a079dd8434184c5fc855b9d83c36ddd7'
                                      ]);

        $this->addMeta($anatolie);
        PermissionManager::assign($anatolie, UserRoles::USER);

        $sergio = \App\User::create([
                                        'name' => "Sergio Castro",
                                        'email' => 'sergio@spectero.com',
                                        'password' => \Illuminate\Support\Facades\Hash::make('temppass'),
                                        'status' => \App\Constants\UserStatus::ACTIVE,
                                        'node_key' => 'f1bb455e4c96a167eb42c66a0ba75d8a966d58d9bc664071aef4e76405705849'
                                    ]);

        $this->addMeta($sergio);
        PermissionManager::assign($sergio, UserRoles::USER);

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

        $blank = \App\User::create([
                                        'name' => "Blank User",
                                        'email' => 'blank@spectero.com',
                                        'password' => \Illuminate\Support\Facades\Hash::make('temppass'),
                                        'status' => \App\Constants\UserStatus::ACTIVE,
                                        'node_key' => \App\Libraries\Utility::getRandomString(2)
                                    ]);

        $this->addMeta($blank);
        PermissionManager::assign($blank, UserRoles::USER);

    }

        private function addMeta(\App\User $user)
        {
            $stripeTokens = [
                'tok_visa',
                'tok_visa_debit',
                'tok_mastercard',
                'tok_mastercard_debit',
                'tok_mastercard_prepaid',
                'tok_amex',
                'tok_discover',
                'tok_diners',
                'tok_jcb'
            ];

            try
            {
                UserMeta::addOrUpdateMeta($user,UserMetaKeys::AddressLineOne, "300 Delaware Ave.");
                UserMeta::addOrUpdateMeta($user,UserMetaKeys::Organization, 'Spectero');
                UserMeta::addOrUpdateMeta($user, UserMetaKeys::TaxIdentification, 'FRC-3-612A1521');
                UserMeta::addOrUpdateMeta($user, UserMetaKeys::PreferredCurrency, 'USD');
                UserMeta::addOrUpdateMeta($user, UserMetaKeys::City, 'Wilmington');
                UserMeta::addOrUpdateMeta($user, UserMetaKeys::State, 'Delaware');
                UserMeta::addOrUpdateMeta($user, UserMetaKeys::Country, 'US');
                UserMeta::addOrUpdateMeta($user, UserMetaKeys::PostCode, 19801);
                UserMeta::addOrUpdateMeta($user, UserMetaKeys::StripeCardToken, array_random($stripeTokens));
            }
            catch (\App\Errors\FatalException $e)
            {

            }
    }
}
