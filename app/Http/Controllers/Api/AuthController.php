<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Auth\AuthenticationException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

// controller for routes regarding the auth tokens
class AuthController extends Controller
{
    protected $client;
    
    // allows to insert a mock client for testing
    public function __construct(Client $client = null){
        $this->client = $client ?: new Client(['http_errors' => false]);
    }
    
    
    public function grant(Request $request){
    // returns a json web token upon proper credentials     
        
        //gets user
        $user = User::where('email', $request->email)->first();
        if($user ==null){
            return response()->json(['error'=> 'User not found'],404);
        }
        
        if(!$user->email_verified_at){
            return response()->json(['error'=> 'User needs to verify email address'],400);
        }
        // runs another route with client information not known outside the app
        $response = $this->client->post(\config('authController.app_url') . \config('authController.auth_grant_prefix') . '/oauth/token', [
            'form_params' => [
            'grant_type' => 'password',
            'client_id' => \config('authController.client_id'),
            'client_secret' => \config('authController.client_secret'),
            'username' => $request->email,
            'password' => $request->password,
            'scope' => '',
            ],
        ]);
        // throws exception if returned status code is not 200
        if($response->getStatusCode()!= 200){
            throw new AuthenticationException();
        }
        $array = json_decode((string) $response->getBody(), true);
        $array['id'] = $user->id;
        $array['rank']=$user->rank;
        return $array;
        
        }
        public function refresh(Request $request){
            // this for the refresh token 
            
            //runs another route with client information not know outside the app
            $response = $this->client->post(\config('authController.app_url') . \config('authController.auth_grant_prefix') . '/oauth/token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $request->token,
                    'client_id' => \config('authController.client_id'),
                    'client_secret' => \config('authController.client_secret'),
                    'scope' => '',
                ],
            ]);
            // throws an exception if status code is not 200
            if($response->getStatusCode()!= 200){
                throw new AuthenticationException();
            }
            return json_decode((string) $response->getBody(), true);
        }
        
}
