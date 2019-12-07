<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;




class ResetPasswordController extends Controller
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

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/home';
    
    public function reset(Request $request)
    {
        $request->validate($this->rules(), $this->validationErrorMessages());
        
      
        $response = $this->broker()->reset(
            $this->credentials($request), function ($user, $password) {
                
                $this->resetPassword($user, $password);
            }
        );
        
        
        return $response == Password::PASSWORD_RESET
        ? response()->json(['message' => 'Everything Worked out'],200)
        
        : response()->json(['message' => 'Password not reset'],400);
        
    }
    protected function rules()
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|between:8,255',
        ];
    }
    
   
}
