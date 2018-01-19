<?php

use Illuminate\Database\Seeder;

class BillingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\OrderLineItem::class, 5)->create();
        factory(App\Order::class, 5)->create();
        factory(App\Invoice::class, 5)->create();
    }
}
