<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Auth\AuthenticationException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;


class AuthController extends Controller
{
    protected $client;
    
    
    public function __construct(Client $client = null){
        $this->client = $client ?: new Client(['http_errors' => false]);
    }
    public function grant(Request $request){
        
        
        $user = User::where('email', $request->email)->first();
        if($user ==null){
            return response()->json(['error'=> 'User not found'],404);
        }
        if(!$user->email_verified_at){
            return response()->json(['error'=> 'User needs to verify email address'],400);
        }
        
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
        
        if($response->getStatusCode()!= 200){
            throw new AuthenticationException();
        }
        return json_decode((string) $response->getBody(), true);
        
        }
        public function refresh(Request $request){
            
            $response = $this->client->post(\config('authController.app_url') . \config('authController.auth_grant_prefix') . '/oauth/token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $request->token,
                    'client_id' => \config('authController.client_id'),
                    'client_secret' => \config('authController.client_secret'),
                    'scope' => '',
                ],
            ]);
            if($response->getStatusCode()!= 200){
                throw new AuthenticationException();
            }
            return json_decode((string) $response->getBody(), true);
        }
        
}
