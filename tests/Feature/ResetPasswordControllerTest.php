<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CustomResetPasswordNotification;

class ResetPasswordControllerTest extends TestCase
{
        use DatabaseTransactions;

        
        public function  testForgotPassword(){
            Mail::fake();
            Notification::fake();
            $this->withoutMiddleWare();
            $user =$this->createVerifiedUser();
            $response = $this->post(route('password.email'),['email' =>$user->email]);
            $response->assertOk();
            
            Notification::assertSentTo([$user], CustomResetPasswordNotification::class, function($notification, $channels) use ($user){
                $response = $this->post(route('password.update'), ['email' => $user->email, 'password'=> 'thingathing', 'password_confirmation' => 'thingathing', 'token' => 'allegory']);
                $response->assertStatus(400);
                $response = $this->post(route('password.update'), ['email' => $user->email, 'password'=> 'thingathing', 'password_confirmation' => 'thingathing', 'token' => $notification->token]);
                $user->refresh();
                $this->assertTrue(Hash::check('thingathing', $user->password));
                return true;
            });
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
        
        
        
       
}
