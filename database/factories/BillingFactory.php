<?php

$autoIncrement = autoIncrement();

$factory->define(App\OrderLineItem::class, function (Faker\Generator $faker) use ($autoIncrement) {
    return [
        'description' => $faker->paragraph,
        'order_id' => $autoIncrement->current(),
        'type' => \App\Constants\PaymentType::DEBIT,
        'resource' => $faker->word,
        'quantity' => $faker->randomDigit,
        'amount' => $faker->randomDigit,
    ];
});

$factory->define(App\Order::class, function (Faker\Generator $faker) {
    return [
        'user_id' => 6,
        'status' => $faker->word,
        'subscription_reference' => $faker->word,
        'subscription_provider' => $faker->word,
    ];
});

$factory->define(App\Invoice::class, function (Faker\Generator $faker) use ($autoIncrement) {
    return [
        'order_id' => $autoIncrement->current(),
        'amount' => $faker->randomDigit,
        'currency' => \App\Constants\Currency::USD,
        'status' => $faker->word,
    ];
});

function autoIncrement()
{
    for ($i = 0; $i < 6; $i++)
        yield $i;
}
