<?php

namespace App\Http\Controllers\Api;

use App\Event;
use App\EventPhoto;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate;

class EventPhotoController extends Controller
{
    public function fileAdd(Request $request, $id)
    {
        $user= Auth::user();
        $event = Event::with('eventPhotos')->findOrFail($id);
        if($event->user_id != $user->id && ($user->rank != 'elevated' || $user->rank != 'root')){
            throw  new Illuminate\Auth\Access\AuthorizationException('Unauthorized user');
        }
        $this->validate($request, ['file' =>'required', 'file.*' => 'mimes:jpeg,jpg,png,gif']);
        
        foreach($request->file('file') as $file){
            
            $path = $file->store('docs','public');
            $event_photo = EventPhoto::create(['filename' => $path]);
            $event->eventPhotos()->save($event_photo);
        }
        
        $event->refresh();
        return $event;
    }
    
    public function fileRemove(Request $request, $id){
        $photo = EventPhoto::findOrFail($id);
        $user= Auth::user();
        $event_id = $photo->event_id;
        $event= Event::findOrFail($event_id);
        if($event->user_id != $user->id && ($user->rank != 'elevated' || $user->rank != 'root')){
            throw  new Illuminate\Auth\Access\AuthorizationException('Unauthorized user');
        }
        $path = $photo->filename;
        $photo->delete();
        Storage::delete($path);
        return response()->noContent(200);
        
        
    }
    public function fileGet(Request $request, $id){
        $photo = EventPhoto::findOrFail($id);
        return $photo;
    }
}
