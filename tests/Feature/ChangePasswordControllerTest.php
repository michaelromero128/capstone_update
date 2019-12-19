<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;

class ChangePasswordControllerTest extends TestCase
{
        use DatabaseTransactions;

        
        public function testChangePassword(){
            $this->withoutMiddleware();
            $user =$this->createVerifiedUser();
            $response = $this->post(route('password.change'),[
                'email' => $user->email,
                'current_password' => 'testtest',
                'new_password' => 'thingathing',
                'new_password_confirmation' => 'thingathing'
                
            ]);
            
            $response->assertOk();
            $user->refresh();
            $this->assertTrue(Hash::check('thingathing', $user->password));
        }
        
        public function testBadChangePassword(){
            $this->withoutMiddleware();
            $user =$this->createVerifiedUser();
            $response = $this->post(route('password.change'),[
                'email' => $user->email,
                'current_password' => 'testatest',
                'new_password' => 'thingathing',
                'new_password_confirmation' => 'thingathing'
                
            ]);
            
            $response->assertStatus(401);
            $user->refresh();
            $this->assertFalse(Hash::check('thingathing', $user->password));
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
        
        
        
       
}
