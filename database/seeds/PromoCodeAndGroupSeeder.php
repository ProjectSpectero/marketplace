<?php

use Illuminate\Database\Seeder;

class PromoCodeAndGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws Throwable
     */
    public function run()
    {
        $group = new \App\PromoGroup();
        $group->name = 'Spectero Test Promos';
        $group->usage_limit = 100;

        $group->saveOrFail();

        $code = new \App\PromoCode();
        $code->code = 'TEST-PROMO-CODE';
        $code->group_id = $group->id;
        $code->usage_limit = 100;
        $code->amount = 10;
        $code->expires = \Carbon\Carbon::now()->addDays(30);

        $code->saveOrFail();

    }
}
