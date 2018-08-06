<?php

$factory->define(App\NodeGroup::class, function (Faker\Generator $faker) {
    return [
        'friendly_name' => $faker->colorName,
        'status' => 'ENABLED',
        'user_id' => $faker->numberBetween(6, 8),
        'market_model' => array_random(\App\Constants\NodeMarketModel::getConstraints()),
        'price' => $faker->numberBetween(5, 200),
    ];
});