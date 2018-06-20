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
          'node_key' => \App\Libraries\Utility::getRandomString(2)
        ]);

        $this->addMeta($admin);
        PermissionManager::assign($admin, UserRoles::ADMIN);

        $anatolie = \App\User::create([
                                          'name' => "Anatolie Diordita",
                                          'email' => 'anatolie@spectero.com',
                                          'password' => \Illuminate\Support\Facades\Hash::make('temppass'),
                                          'status' => \App\Constants\UserStatus::ACTIVE,
                                          'node_key' => \App\Libraries\Utility::getRandomString(2)
                                      ]);

        $this->addMeta($anatolie);
        PermissionManager::assign($anatolie, UserRoles::USER);

        $sergio = \App\User::create([
                                        'name' => "Sergio Castro",
                                        'email' => 'sergio@spectero.com',
                                        'password' => \Illuminate\Support\Facades\Hash::make('temppass'),
                                        'status' => \App\Constants\UserStatus::ACTIVE,
                                        'node_key' => "a74aeb5b6012763e58b8423b054e3bb840ffb001aa4798b584317aab12662686"
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
