<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Event;
use App\Zipcode;
use Faker\Generator as Faker;


$factory->define(Event::class, function (Faker $faker) {
    $start = $faker->dateTimeBetween('-10 days', '+1 years');
    $end = $faker->dateTimeBetween($start, $start->format('Y-m-d').' +366 days');
    return [
        'event_title' => $faker->sentence,
        'event_details' => $faker->text,
        'host_organization' => $faker->name,
        'event_coordinator_name' => $faker->name,
        'event_coordinator_email' => $faker->email,
        'event_coordinator_phone' => $faker->phoneNumber,
        'start_date' =>$start,
        'end_date' =>$end,
        'start_time' => '3pm',
        'end_time' => '6pm',
        'requirements_major' => $faker->sentence,
        'age_requirement' => $faker->numberBetween(18,29),
        'minimum_hours' => $faker->numberBetween(1,4),
        'tags' => $faker->text,
        'category' => $faker->randomElement(['Service','Civic Action Scorecard Points','Internship','Employement','Community Event']),
        'shifts' => $faker->sentence,
        'city' => $faker->city,
        'address' => $faker->address,
        'zipcode' => Zipcode::orderByRaw("RAND()")->first()->zipcode,
        'featured' => $faker->randomElement([0,1]),
        'user_id' => 1,
    ];
});
