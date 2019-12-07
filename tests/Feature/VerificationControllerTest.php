<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use App\Mail\Resend;

class VerificationControllerTest extends TestCase
{
        use DatabaseTransactions;
        
        public function  testResend()
        {
            Mail::fake();
            $params=$this->createTestUserParams();
            $response = $this->post(route('register'),$params);
            $response->assertOk();
            $user = User::where('email',config('authController.test_email'))->first();
            if($user){
                $response = $this->post(route('email.customResend'), ['email' => $user->email]);
                $response->assertOk();
                Mail::assertQueued(Resend::class);
            }else{
                $this->fail('should find a user');
            }
        }
        
        public function testBadResend(){
            Mail::fake();
            $response = $this->post(route('email.customResend'), ['email' => 'bad@bad.bad']);
            $response->assertStatus(404);
            Mail::assertNothingQueued();
            
        }
        public function testVerify(){
            $params = $this->createTestUserParams();
            $response = $this->post(route('register'), $params);
            $response->assertOk();
            $user = User::where('email',config('authController.test_email'))->first();
            if($user){
                $token = $user->verifyUser->token;
                $id = $user->verifyUser->user_id;
                $response2 = $this->post(route('email.customVerify'), ['user_id' => $id, 'token' => $token]);
                $response2->assertOk();
                $user = $user->refresh();
                $this->assertNotNull($user->email_verified_at);
            }else{
                $this->fail('should find a user');
            }
        }
        public function testBadVerify(){
            $params = $this->createTestUserParams();
            $response = $this->post(route('register'), $params);
            $response->assertOk();
            $user = User::where('email',config('authController.test_email'))->first();
            if($user){
                $token = 'abcd';
                $id = $user->verifyUser->user_id;
                $response2 = $this->post(route('email.customVerify'), ['user_id' => $id, 'token' => $token]);
                $response2->assertStatus(400);
                $user = $user->refresh();
                $this->assertNull($user->email_verified_at);
            }else{
                $this->fail('should find a user');
            }
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
        
        
        
        
        
        
}
