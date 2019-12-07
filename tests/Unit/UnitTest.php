<?php

namespace Tests\Unit;

use App\Event;
use App\EventPhoto;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UnitTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    
    use DatabaseTransactions;
    public function testUser()
    {
        $this->userParams();
        $user = User::create($this->userParams);
        $this->assertEquals($user->title,'test');
        $this->assertEquals($user->name,'test');
        $this->assertEquals($user->location,'test');
        $this->assertEquals($user->phone,'test');
        $this->assertEquals($user->email,'michaelsnewspam@gmail.com');
        $this->assertEquals($user->password, 'testtest');
        $this->assertEquals('test', $user->rank);

        
    }
    
    public function testEvent(){
        $this->userParams();
        $user = User::create($this->userParams);
        $params = $this->eventParams();
        $event = Event::create($params);
        $this->assertEquals('test',$event->event_title);
        $this->assertEquals('test',$event->event_details);
        $this->assertEquals('test',$event->host_organization);
        $this->assertEquals('test',$event->event_coordinator_name);
        $this->assertEquals('test',$event->event_coordinator_phone);
        $this->assertEquals('test',$event->event_coordinator_email);
        $this->assertEquals('2020-10-10',$event->start_date);
        $this->assertEquals('2020-10-11',$event->end_date);
        $this->assertEquals('test',$event->start_time);
        $this->assertEquals('test',$event->end_time);
        $this->assertEquals('test',$event->requirements_major);
        
        $this->assertEquals('18',$event->age_requirement);
        $this->assertEquals('2',$event->minimum_hours);
        $this->assertEquals('test',$event->tags);
        $this->assertEquals('test',$event->category);
        $this->assertEquals('test',$event->shifts);
        $this->assertEquals('test',$event->city);
        $this->assertEquals('test',$event->address);
        $this->assertEquals(0,$event->featured);
        $this->assertEquals('210',$event->zipcode);
        $this->assertEquals(43.00590, $event->lat);
        $this->assertEquals(-71.01320,$event->lon);
        $user->events()->save($event);
        $this->assertEquals($user->id,$event->user_id);
        
    }
    public function testEventPhoto(){
        $this->userParams();
        $user = User::create($this->userParams);
        $params = $this->eventParams();
        $event = Event::create($params);
        $user->events()->save($event);
        $eventPhoto = EventPhoto::create(['filename' => 'test']);
        $event->eventPhotos()->save($eventPhoto);
        $this->assertEquals('test', $eventPhoto->filename);
        $this->assertEquals($event->id, $eventPhoto->event_id);
        
    }
    
    private function userParams(){
        $params= [
          'title' => 'test',
          'name' => 'test',
          'location' => 'test',
          'phone' => 'test',
          'email' => 'michaelsnewspam@gmail.com',
          'password' => 'testtest',
          'rank' => 'test'
        ];
        
        $this->userParams = $params;
    }
    private function eventParams(){
        $params = [
          'event_title' => 'test',
            'event_details' => 'test',
            'host_organization' => 'test',
            'event_coordinator_name' => 'test',
            'event_coordinator_phone' => 'test',
            'event_coordinator_email' => 'test',
            'start_date' => '2020-10-10',
            'end_date' => '2020-10-11', 
            'start_time' => 'test',
            'end_time' => 'test',
            'requirements_major' => 'test',
            'featured' => 0,
            'age_requirement' => 18,
            'minimum_hours' => 2,
            'tags' => 'test',
            'category' => 'test',
            'shifts' => 'test',
            'city' => 'test',
            'address' => 'test',
            'zipcode' => '210',
        ];
        return $params;
        
    }
}
