<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;
use \Mockery;

class AuthControllerTest extends TestCase
{
    use DatabaseTransactions;
    
    public function testGetAuthToken(){
        $user =$this->createVerifiedUser();
        $params = [
            'email' =>$user->email,
            'password' =>'testtest',
        ];
        $out = new Response(200);//
        
        $client = Mockery::mock(Client::class);
        $client->makePartial()->shouldReceive('request')->once()->andReturn($out);
        $this->app->instance(Client::class, $client);
        $response = $this->post(route('auth_grant'), $params);
        $response->assertOk();
        \Mockery::close();
    }
    
    public function testBadGetAuthToken(){
        $user =$this->createVerifiedUser();
        $params = [
            'email' =>$user->email,
            'password' =>'testatest',
        ];
        $out = new Response(400);//
        
        $client = Mockery::mock(Client::class);
        $client->makePartial()->shouldReceive('request')->once()->andReturn($out);
        $this->app->instance(Client::class, $client);
        $response = $this->post(route('auth_grant'), $params);
        $response->assertStatus(401);
        // 401 because the route will throw a authorization exception if anything but a 200 is returned by the guzzle client
        \Mockery::close();
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
