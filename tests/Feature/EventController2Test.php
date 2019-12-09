<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
 class EventController2Test extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testGetEvents(){
        $this->withoutMiddleware();
        $user= User::first();
        
        $this->be($user);
        Storage::fake('public');
        $params = $this->createEventParams();
        $response = $this->call('POST', route('event.store'), $params, [], []);
        $params['start_date']= '1971-10-12';
        $params['end_date'] = '1972-10-12';
        $response = $this->call('POST', route('event.store'), $params, [], []);
        
        $id= json_decode($response->getContent())->id;
        
        $response = $this->get('api/events');
        $response->assertOk();
        $response->assertJsonStructure([
            'data'
        ]);
        $response->assertJsonStructure([
            'data' => [
                '*' =>[
                    'event_title',
                    'event_details'  ,
                    'host_organization',
                    'event_coordinator_name',
                    'event_coordinator_phone',
                    'event_coordinator_email',
                    'start_date',
                    'end_date',
                    'start_time',
                    'end_time',
                    'requirements_major',
                    'age_requirement',
                    'minimum_hours',
                    'tags',
                    'featured',
                    'category',
                    'shifts',
                    'city',
                    'address',
                    'zipcode',
                    'event_photos'
                    
                ],
                '*' =>[
                    'event_title',
                    'event_details'  ,
                    'host_organization',
                    'event_coordinator_name',
                    'event_coordinator_phone',
                    'event_coordinator_email',
                    'start_date',
                    'end_date',
                    'start_time',
                    'end_time',
                    'requirements_major',
                    'age_requirement',
                    'minimum_hours',
                    'tags',
                    'featured',
                    'category',
                    'shifts',
                    'city',
                    'address',
                    'zipcode',
                ]
            ]
        ]);
        
        $response = $this->get('api/events/' . -33);
        $response->assertStatus(404);
        
        // test variants of a get, some parameters add to the query, others return a different query
        $response = $this->get('api/events?zipcode=33187&q=uniquestringofpoweromg&range=10000&date=2020-10-10');
        $response->assertOk();
        $response->assertJsonStructure([
            'data'
        ])->assertJsonFragment(['tags' => 'uniquestringofpoweromg']); 
        
        $response = $this->get('api/events?orderBy=zipcode&q=uniquestringofpoweromg&featured=0&old=yes');
        $response->assertOk();
        $response->assertJsonStructure([
            'data'
        ])->assertJsonFragment(['tags' => 'uniquestringofpoweromg']);
        $response = $this->get('api/events?user=1&old=only&q=uniquestringofpoweromg');
        $response->assertOk();
        $response->assertJsonStructure([
            'data'
        ])->assertJsonFragment(['tags' => 'uniquestringofpoweromg']);
        $response = $this->call('DELETE', 'api/events/' . $id, [], [],[]);
        
    }
    
  
  
    

    
    private function createEventParams(){
        
        $params = [
            'event_title' => 'test',
            'event_details' => 'test',
            'host_organization' => 'test',
            'event_coordinator_name' => 'test',
            'event_coordinator_phone' => 'test',
            'event_coordinator_email' => 'test@test.test',
            'start_date' => '2020-10-10',
            'end_date' => '2020-10-11',
            'start_time' => 'test',
            'end_time' => 'test',
            'requirements_major' => 'test',
            'age_requirement' => 18,
            'minimum_hours' => 2,
            'tags' => 'uniquestringofpoweromg',
            'category' => 'test',
            'shifts' => 'test',
            'city' => 'test',
            'address' => 'test',
            'zipcode' => '210',
            
        ];
        return $params;
    }
    
    
}
