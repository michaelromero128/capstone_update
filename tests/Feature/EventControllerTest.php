<?php

namespace Tests\Feature;

 use Illuminate\Foundation\Testing\WithFaker;
 use Illuminate\Auth\Events\Registered;
 use Illuminate\Auth\Events\Verified;
 use Tests\TestCase;
 use App\EventPhoto;
 use App\User;
 use Illuminate\Foundation\Testing\DatabaseTransactions;
 use Illuminate\Support\Facades\DB;
 use Illuminate\Support\Facades\Event;
 use Illuminate\Support\Facades\Hash;
 use Illuminate\Support\Facades\Mail;
 use Illuminate\Support\Facades\Notification;
 use Illuminate\Support\Facades\Storage;
 use App\Mail\Resend;
 use App\Mail\Verify;
 use GuzzleHttp\Psr7\Response;
 use GuzzleHttp\Client;
 use \Mockery;
 use Illuminate\Foundation\Testing\WithoutMiddleware;
 use Illuminate\Http\UploadedFile;
 use App\Notifications\CustomResetPasswordNotification;
class EventControllerTest extends TestCase
{
        use DatabaseTransactions;

        public function testCreateEvent(){
            $this->withoutMiddleware();
            $user= $this->createVerifiedUser();
            $file = UploadedFile::fake()->image('random.jpg');
            $this->be($user);
            Storage::fake('public');
            $params = $this->createEventParams();
            $response = $this->call('POST', route('event.store'), $params, [], ['file' => [$file]]);
            $response->assertOk()->assertJson([
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
                'tags' => 'test',
                'category' => 'test',
                'featured' => 0,
                'shifts' => 'test',
                'city' => 'test',
                'address' => 'test',
                'zipcode' => '210',
                'lat' => 43.00590,
                'lon' => -71.01320,
                'user_id' => $user->id,
            ]);
            $this->assertEquals('docs/' . $file->hashName(), EventPhoto::latest()->first()->filename);
            Storage::disk('public')->assertExists('docs/' .$file->hashName());
        }
        public function testCreateBadEvent(){
            $this->withoutMiddleware();
            $user= $this->createVerifiedUser();
            $file = UploadedFile::fake()->image('random.jpg');
            $this->be($user);
            Storage::fake('public');
            $params = $this->createBadEventParams();
            $response = $this->call('POST', route('event.store'), $params, [], ['file' => [$file]]);
            $response->assertStatus(422);
        }
        public function testGetOneEvent(){
            $this->withoutMiddleware();
            $user= $this->createVerifiedUser();
            $file = UploadedFile::fake()->image('random.jpg');
            $this->be($user);
            Storage::fake('public');
            $params = $this->createEventParams();
            $response = $this->call('POST', route('event.store'), $params, [], ['file' => [$file]]);
            $id =$response->json('id');
            $response = $this->get('api/events/' . $id);
            $response->assertOk();
            $response->assertJson([
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
                'featured' => 0,
                'tags' => 'test',
                'category' => 'test',
                'shifts' => 'test',
                'city' => 'test',
                'address' => 'test',
                'zipcode' => '210',
            ]);
            $response = $this->get('api/events/' . -33);
            $response->assertStatus(404);
        }
        public function testGetEvents(){
            $this->withoutMiddleware();
            $user= $this->createVerifiedUser();
            $file = UploadedFile::fake()->image('random.jpg');
            $this->be($user);
            Storage::fake('public');
            $params = $this->createEventParams();
            $response = $this->call('POST', route('event.store'), $params, [], ['file' => [$file]]);
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
            
            // test variants of a get
            $response = $this->get('api/events?zipcode=33187&range=10000&q=test&date=2020-10-10');
            $response->assertOk();
            $response->assertJsonStructure([
                'data'
            ]);
            $response = $this->get('api/events?orderBy=zipcode&featured=1&old=yes');
            $response->assertOk();
            $response->assertJsonStructure([
                'data'
            ]);
            $response = $this->get('api/events?user=1&old=only');
            $response->assertOk();
            $response->assertJsonStructure([
                'data'
            ]);
           
        }
        
        public function testPutEvent(){
            $this->withoutMiddleware();
            $user= $this->createVerifiedUser();
            $file = UploadedFile::fake()->image('random.jpg');
            $this->be($user);
            Storage::fake('public');
            $params = $this->createEventParams();
            $response = $this->call('POST', route('event.store'), $params, [], ['file' => [$file]]);
            $id =$response->json('id');
            $params= ['event_title' => 'thing'];
            $response = $this->call('PUT', 'api/events/' . $id, $params, [],[]);
            $response->assertOk();
            $response->assertJson([
                'event_title' => 'thing',
            ]);
            $response = $this->call('PUT', 'api/events/' . -33, $params, [],[]);
            $response->assertStatus(404);
        }
        public function testDeleteEvent(){
            $this->withoutMiddleware();
            $user= $this->createVerifiedUser();
            $file = UploadedFile::fake()->image('random.jpg');
            $this->be($user);
            Storage::fake('public');
            $params = $this->createEventParams();
            $response = $this->call('POST', route('event.store'), $params, [], ['file' => [$file]]);
            $id =$response->json('id');
            $params= ['event_title' => 'thing'];
            $response = $this->call('DELETE', 'api/events/' . $id, [], [],[]);
            $response->assertOk();
            $response = $this->call('GET', 'api/events/' . $id, [], [],[]);
            $response->assertStatus(404);
            $response = $this->call('DELETE', 'api/events/' . $id, [], [],[]);
            $response->assertStatus(404);
        }
        private function createTestUserParams(){
            $params = [
                'title' => 'test',
                'name' => 'test',
                'location' => 'test',
                'phone' => 'test',
                'email' => config('authController.test_email'),
                'password' => 'testtest',
                'password_confirmation' => 'testtest'
            ];
            return $params;
        }
        private function createVerifiedUser(){
            $this->createTestUserParams();
            $this->post(route('register'), $this->createTestUserParams());
            $user = User::where('email',config('authController.test_email'))->first();
            $token = $user->verifyUser->token;
            $id = $user->verifyUser->user_id;
            $this->post(route('email.customVerify'), ['user_id' => $id, 'token' => $token]);
            
            $user=$user->refresh();
            return $user;
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
                'tags' => 'test',
                'category' => 'test',
                'shifts' => 'test',
                'city' => 'test',
                'address' => 'test',
                'zipcode' => '210',
                
            ];
            return $params;
        }
        
        private function createBadEventParams(){
            
            $params = [
                //'event_title' => 'test',
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
