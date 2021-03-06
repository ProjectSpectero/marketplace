<?php


use App\Libraries\Utility;

$factory->define(App\OrderLineItem::class, function (Faker\Generator $faker) {
    return [
        'description' => $faker->paragraph,
        'order_id' => 2,
        'type' => \App\Constants\OrderResourceType::NODE,
        'resource' => mt_rand(1, 5),
        'quantity' => 1,
        'amount' => 9.99,
    ];
});

$factory->define(App\Order::class, function (Faker\Generator $faker) {
    return [
        'user_id' => mt_rand(6, 10),
        'status' => array_random(\App\Constants\OrderStatus::getConstants()),
        'subscription_reference' => \App\Libraries\Utility::getRandomString(),
        'subscription_provider' => array_random(\App\Constants\PaymentProcessor::getConstants()),
        'accessor' => Utility::getRandomString() . ':' . Utility::getRandomString(),
        'due_next' => $faker->dateTimeBetween('-7 days', '+7 days'),
        'term' => 30
    ];
});

$factory->define(App\Invoice::class, function (Faker\Generator $faker) {
    return [
        'id' => mt_rand(1, 10000),
        'order_id' => 2,
        'user_id' => mt_rand(1, 6),
        'amount' => 49.95,
        'currency' => \App\Constants\Currency::USD,
        'status' => \App\Constants\InvoiceStatus::UNPAID,
        'due_date' => \Carbon\Carbon::now(),
    ];
});

$factory->define(App\Transaction::class, function (Faker\Generator $faker) {
    return [
        'invoice_id' => mt_rand(1, 5),
        'payment_processor' => array_random(\App\Constants\PaymentProcessor::getConstants()),
        'reference' => $faker->unique()->numberBetween(1, 5),
        'type' => \App\Constants\PaymentType::DEBIT,
        'reason' => $faker->word,
        'amount' => $faker->numberBetween(5, 100),
        'fee' => $faker->numberBetween(5, 100),
        'currency' => \App\Constants\Currency::USD,
    ];
});
