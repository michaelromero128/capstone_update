<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use App\VerifyUser;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;

use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Mail\Verify;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'title' => ['required','string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string','max:100'],
            'phone' => ['required','string','max:50'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    
    
    
  
    public function register(Request $request)
    {
        
        
        $this->validator($request->all())->validate();
        
       if(\config('authController.status') == 'production' &&   substr($request->email,-7,7) != 'mdc.edu'){
           return response()->json(['message' => 'not a valid mdc.edu email'], 400);
       }
       
       $params = [
           'title' => htmlentities($request->input('title','')),
           'name' => htmlentities($request->input('name','')),
           'location' => htmlentities($request->input('location','')),
           'phone' => htmlentities($request->input('phone','')),
           'email' => $request->input('email'),
           'rank' => 'reg',
           'password' => Hash::make($request->input('password')),
           
       ];
        $user = User::create($params);
        VerifyUser::create([
            'user_id' => $user->id,
            'token' => substr(sha1(time()),0,7)
        ]);
        //Mail::queue(new Verify($user))->to($user->email);
         Mail::to($user->email)->queue(new Verify($user));
        
        
        
        return User::where('email', $request->email)->first()
        ? response()->json(['message' => 'Everything is swell'],200): response()->json(['message' => 'Everything is not swell'],400);
        
        
        
        
    
    }
    
    protected function failedValidation(Validator $validator) {
        return response()->json(['message' => 'failed validation'], 422);
    }
    
   
}
