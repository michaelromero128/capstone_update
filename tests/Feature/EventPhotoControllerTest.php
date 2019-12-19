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

class EventPhotoControllerTest extends TestCase
{
        use DatabaseTransactions;

        
        
        

        public function testPost(){
            $this->withoutMiddleware();
            $user= $this->createVerifiedUser();
            
            $this->be($user);
            Storage::fake('public');
            $params = $this->createEventParams();
            $response = $this->call('POST', route('event.store'), $params, [], []);
            $id =$response->json('id');
            $file2 = UploadedFile::fake()->image('random.jpg');
            $response = $this->call('POST', 'api/file/' . $id, $params, [], ['file' => [$file2]]);
            $response->assertStatus(200);
            
            $response->assertJsonStructure( [
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
                'category',
                'shifts',
                'city',
                'address',
                'zipcode',
                'event_photos'
                
            ],);
            $this->assertEquals('docs/' . $file2->hashName(), EventPhoto::latest()->first()->filename);
            Storage::disk('public')->assertExists('docs/' . $file2->hashName());
            $this->assertEquals($id,$response->json('id'));
        }
        public function testGet(){
            $this->withoutMiddleware();
            $user= $this->createVerifiedUser();
            
            $this->be($user);
            Storage::fake('public');
            $params = $this->createEventParams();
            $response = $this->call('POST', route('event.store'), $params, [], []);
            $id =$response->json('id');
            $file2 = UploadedFile::fake()->image('random.jpg');
            $response = $this->call('POST', 'api/file/' . $id, $params, [], ['file' => [$file2]]);
            $response->assertStatus(200);
            $photo = EventPhoto::latest()->first();
            $photoId=$photo->id;
            $eventPhotoArray= $response->json('event_photos')[0];
            $photoId = $eventPhotoArray['id'];
            $response = $this->call('GET', 'api/file/' . $photoId, $params, [], []);
            $response->assertStatus(200);
            $response->assertJsonStructure(['id','filename','created_at','updated_at','event_id']);
            $response = $this->call('GET', 'api/file/' . -33, $params, [], []);
            $response->assertStatus(404);
            
        }
        
        public function testDelete(){
            $this->withoutMiddleware();
            $user= $this->createVerifiedUser();
            
            $this->be($user);
            Storage::fake('public');
            $params = $this->createEventParams();
            $response = $this->call('POST', route('event.store'), $params, [], []);
            $id =$response->json('id');
            $file2 = UploadedFile::fake()->image('random.jpg');
            $response = $this->call('POST', 'api/file/' . $id, $params, [], ['file' => [$file2]]);
            $response->assertStatus(200);
            $photo = EventPhoto::latest()->first();
            $name=$photo->filename;
            $photoId=$photo->id;
            $eventPhotoArray= $response->json('event_photos')[0];
            $photoId = $eventPhotoArray['id'];
            $name = $eventPhotoArray['filename'];
            $response = $this->call('DELETE', 'api/file/' . $photoId, $params, [], []);
            $response->assertStatus(200);
            $response = $this->call('GET', 'api/file/' . $photoId, $params, [], []);
            $response->assertStatus(404);
         
            $response = $this->call('DELETE', 'api/file/' . $photoId, $params, [], []);
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
            $id = $user->email;
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
        
        
}
