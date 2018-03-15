<?php

$factory->define(App\NodeIPAddress::class, function (Faker\Generator $faker) {
    return [
        'ip' => $faker->ipv4,
        'node_id' => $faker->numberBetween(1, 100),
        'asn' => $faker->numberBetween(1, 65534),
        'city' => $faker->city,
        'cc' => $faker->countryCode
    ];
});
