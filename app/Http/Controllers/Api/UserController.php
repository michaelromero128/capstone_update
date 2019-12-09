<?php

namespace App\Http\Controllers\Api;

use App\Event;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function changeRank(Request $request){
        // changes the user rank
        $user = Auth::user();
        $user = User::findOrFail($user->getAuthIdentifier());
        // checks if user is permitted
        if($user->rank == 'root'){
            $request->validate(['user_id' => 'required | numeric', 'change' => 'required | string | max:255']);
            $changed_user = User::findOrFail($request->input('user_id'));
            
            // doesn't not allow changing the rank of root
            if($changed_user->rank == 'root'){
                return response()->json(['message' => 'can not change root rank'],400);
            }
            // changes rank based on input
            if($request->input('change') == 'elevate'){
                $changed_user->rank= 'elevated';
                $changed_user->save();
                return response()->json(['message' => 'user Elevated'], 200);
            }
            if($request->input('change') == 'demote'){
                $changed_user->rank= 'reg';
                $changed_user->save();
                return response()->json(['message' => 'user demoted'], 200);
            }
            return response()->json(['message' => 'no change happened'], 400);
            
        }
        return response()->json(['message' => 'unauthorized'], 401);
        
    }
    
    // get for user profile
    public function getProfile(Request $request, $id){
        $user = User::findOrFail($id);
        
        return $user;
        
    }
    
    
}
