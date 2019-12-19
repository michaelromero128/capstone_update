<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\Verify;

class RegisterControllerTest extends TestCase
{
        use DatabaseTransactions;

        public function testRegister()
        {
            Mail::fake();
            
            $response = $this->post(route('register'),$this->createTestUserParams());
            $response->assertOk();
            $user = User::where('email',config('authController.test_email'))->first();
            if($user){
                $this->assertEquals('test',$user->title);
                $this->assertEquals($user->name,'test');
                $this->assertEquals($user->location,'test');
                $this->assertEquals($user->phone, 'test');
                $this->assertEquals($user->email,config('authController.test_email'));
                $this->assertEquals($user->rank,'reg');
                $this->assertTrue(Hash::check('testtest', $user->password));
                $this->assertNotNull($user->verifyUser);
                $this->assertNull($user->email_verified_at);
                Mail::assertQueued(Verify::class, function ($mail) use($user){
                    
                    return $mail->hasTo($user->email);
                });
            }else{
                $this->fail('should find a user');
            }
        }
        
        public function testBadRegister(){
            Mail::fake();
            
            $response = $this->post(route('register'),$this->createBadTestUserParams());
            $response->assertStatus(422);
            Mail::assertNothingQueued();
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
        private function createBadTestUserParams(){
            $params = [
                'title' => 'test',
                'name' => 'test',
                'location' => 'test',
                'phone' => 'test',
                'email' => config('authController.test_email'),
                'password' => 'testtest',
                'password_confirmation' => 'testatest'
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
