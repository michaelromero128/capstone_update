<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

use App\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;





class ChangePasswordController extends Controller
{
    /*
     |--------------------------------------------------------------------------
     | Password Reset Controller
     |--------------------------------------------------------------------------
     |
     | This controller is responsible for handling password reset requests
     | and uses a simple trait to include this behavior. You're free to
     | explore this trait and override any methods you wish to tweak.
     |MAKE SURE THE LINK WORKS
     */
        
    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    
    
    public function changePassword(Request $request)
    {
        $request->validate(['email' => 'required|email', 'current_password' => 'required | string', 'new_password' => ' confirmed |required | string | between:8,255']);
        
        $user= User::where('email',$request->email)->first();
        if($user == null){
            throw new ModelNotFoundException();
        }
        if (!(Hash::check($request->get('current_password'), $user->password))) {
            // The passwords don't matches
            return response()->json(['message' => 'invalid password'],401);;
        }
        if(strcmp($request->get('current_password'), $request->get('new_password')) == 0){
            //Current password and new password are same
            return response()->json(['message' => 'same as old password'],400);
        }
        if($user->password = Hash::make($request->get('new_password'))){
            $user->setRememberToken(Str::random(60));
            
            $user->save();
            
            event(new PasswordReset($user));
            
            return response()->json(['message' => 'password reset'],200);
            
        }
        return response()->json(['message' => 'something bad happened',400]);
    }
    
    protected function setUserPassword($user, $password)
    {
        $user->password = Hash::make($password);
    }
    
}