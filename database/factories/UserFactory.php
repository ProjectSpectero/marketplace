<?php

$factory->define(App\User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'status' => array_random(\App\Constants\UserStatus::getConstants()),
        'password' => \Illuminate\Support\Facades\Hash::make('temppass'),
        'node_key' => \App\Libraries\Utility::getRandomString(2)
    ];
});
