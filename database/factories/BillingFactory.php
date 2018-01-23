<?php



$factory->define(App\OrderLineItem::class, function (Faker\Generator $faker) {
    return [
        'description' => $faker->paragraph,
        'order_id' => mt_rand(1, 5),
        'type' => \App\Constants\OrderResourceType::NODE,
        'resource' => mt_rand(1, 5),
        'quantity' => mt_rand(1, 5),
        'amount' => $faker->numberBetween(5, 100),
    ];
});

$factory->define(App\Order::class, function (Faker\Generator $faker) {
    return [
        'user_id' => mt_rand(1, 6),
        'status' => array_random(\App\Constants\OrderStatus::getConstants()),
        'subscription_reference' => $faker->word,
        'subscription_provider' => array_random(\App\Constants\PaymentProcessor::getConstants()),
    ];
});

$factory->define(App\Invoice::class, function (Faker\Generator $faker) {
    return [
        'order_id' => mt_rand(1, 5),
        'amount' => $faker->numberBetween(5, 100),
        'currency' => \App\Constants\Currency::USD,
        'status' => array_random(\App\Constants\InvoiceStatus::getConstants()),
    ];
});