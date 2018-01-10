<?php

$factory->define(App\Node::class, function (Faker\Generator $faker) {
    return [
        'ip' => $faker->ipv4,
        'port' => $faker->randomNumber(4),
        'protocol' => 'HTTP',
        'access_token' => $faker->sha1,
        'install_id' => $faker->sha256,
        'status' => \App\Constants\NodeStatus::CONFIRMED,
        'user_id' => $faker->numberBetween(1, 6),
        'market_model' => \App\Constants\NodeMarketModel::UNLISTED,
    ];
});
