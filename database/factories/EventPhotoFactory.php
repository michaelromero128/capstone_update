<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\EventPhoto;
use Faker\Generator as Faker;

$factory->define(EventPhoto::class, function (Faker $faker) {
    return [
        'filename' => 'docs/' . $faker->image('public/docs',800,600,null,false)
    ];
});
