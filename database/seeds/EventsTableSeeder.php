<?php

use Illuminate\Database\Seeder;
use App\Event;

class EventsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Event::class, 120)->create()->each(function($event){
            $event->eventPhotos()->save(factory(App\EventPhoto::class,1)->create()->first());
            $event->eventPhotos()->save(factory(App\EventPhoto::class,1)->create()->first());
        });
    }
}
