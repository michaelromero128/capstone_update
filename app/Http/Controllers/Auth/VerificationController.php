<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Verified;
use App\User;
use App\VerifyUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Mail\Resend;


class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use VerifiesEmails;

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
       // $this->middleware('auth');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }
    
    
    
    public function customVerify(Request $request){
        if(!isset($request->user_id)){
            return response()->json(['message' => 'No user ID'],400);
        }
        if(!isset($request->token)){
            return response()->json(['message' => 'No user token'],400);
        }
        $user = User::where('id',$request->user_id)->first();
        
        if($user == null){
            return response()->json(['message' => 'Bad User Id'],400);
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Already verified'],400);
        }
        
        if($request->token == $user->verifyUser->token){
            if($user->markEmailAsVerified()){
                event(new Verified($user));
                VerifyUser::where('user_id',$user->verifyUser->user_id)->first()->delete();
                return response()->json(['message' => 'Everything is swell'],200);
                
            }else{
                return respone()->json(['message' => 'something bad happened',400]);
            }
        }else{
            return response()->json(['message' => 'Bad token'],400);
            
        }
    }
    public function customResend(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if($user == null){
            throw new ModelNotFoundException();
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Already registered'], 400);
        }
        
        Mail::to($user->email)->queue(new Resend($user));
        
        return response()->json(['message' => 'Email Resent'], 200);
    }
}
