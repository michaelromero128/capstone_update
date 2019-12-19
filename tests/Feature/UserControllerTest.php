<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
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

class UserControllerTest extends TestCase
{
        use DatabaseTransactions;

        public function testUserChange(){
            
            
            $this->withoutMiddleware();
            $user= $this->createVerifiedUser();
            $file = UploadedFile::fake()->image('random.jpg');
            $this->be($user);
            Storage::fake('public');
            $params = $this->createEventParams();
            $response = $this->call('POST', route('event.store'), $params, [], ['file' => [$file]]);
            $userAdmin= User::where('rank', 'root')->first();
            
            $this->be($userAdmin);
            $response = $this->call('POST', route('user.change_rank'), ['user_id' => $user->id, 'change' => 'elevate'], [], []);
            $response->assertOk();
            $user->refresh();
            $this->assertEquals('elevated',$user->rank);
            $response = $this->call('POST', route('user.change_rank'), ['user_id' => $user->id, 'change' => 'demote'], [], []);
            $response->assertOk();
            $user->refresh();
            $this->assertEquals('reg',$user->rank);
            $this->be($user);
            $response = $this->call('POST', route('user.change_rank'), ['user_id' => $userAdmin->id, 'change' => 'demote'], [], []);
            $response->assertStatus(401);
        }
        
        public function testUserGet(){
            
            $this->withoutMiddleware();
            $user= $this->createVerifiedUser();
            $file = UploadedFile::fake()->image('random.jpg');
            $this->be($user);
            Storage::fake('public');
            $params = $this->createEventParams();
            $this->call('POST', route('event.store'), $params, [], ['file' => [$file]]);
            $response = $this->call('GET', 'api/user/profile/' . $user->id, [], [], []);
            $response->assertOk();
            $response->assertJson([
                'id' => $user->id,
                'title' => $user->title,
                'name' => $user->name,
                'location' => $user->location,
                'phone' => $user->phone,
                'email' => $user->email,
                 ]);
            
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
