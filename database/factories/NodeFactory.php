<?php

$factory->define(App\Node::class, function (Faker\Generator $faker) {
    return [
        'ip' => $faker->ipv4,
        'friendly_name' => $faker->colorName,
        'port' => $faker->randomNumber(4),
        'protocol' => 'http',
        'access_token' => 'cloudUser' . ':' . \App\Libraries\Utility::getRandomString(),
        'install_id' => $faker->sha256,
        'status' => array_random(\App\Constants\NodeStatus::getConstants()),
        'user_id' => $faker->numberBetween(6, 10),
        'price' => $faker->numberBetween(5, 100),
        'market_model' => array_random(\App\Constants\NodeMarketModel::getConstants()),
        'group_id' => $faker->numberBetween(1, 5)
    ];
});
