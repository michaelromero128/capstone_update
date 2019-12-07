<?php

namespace App\Http\Controllers\Api;

use App\Event;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\EventPhoto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     *
     */
    public function index(Request $request)
    {
        $orderBy = $request->input('orderBy','start_date');
        $query = Event::with('eventPhotos');
        
        if(isset($request->zipcode)){
           $query = $query->zipcode($request->input('range',25), $request->zipcode);
        }
        if(isset($request->q)){
            $query=$query->search($request->q);
        }
        if(isset($request->featured)){
            $query=$query->where('featured',$request->featured);
        }
        if(isset($request->user)){
            $query = $query->where('user_id',$request->user);
        }
        if(isset($request->date)){
            return $query->daterange($request->date)->with('eventPhotos')->orderBy($orderBy)->simplePaginate($request->input('pagen',8));
        }
        if(isset($request->old) && $request->old == 'yes'){
            return $query->orderBy($orderBy)->simplePaginate($request->input('pagen',8)); 
        }
        if(isset($request->old) && $request->old == 'only'){
            return $query->where('end_date','<',date('Y-m-d'))->orderBy($orderBy)->simplePaginate($request->input('pagen',8));
        }
        return $query->where('end_date','>=',date('Y-m-d'))->orderBy($orderBy)->simplePaginate($request->input('pagen',8));
    }

    //public function searchTerms($request){
    // return Event::search($terms)->get();
    //}

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       
        DB::beginTransaction();
        try{
            $request->validate([
        
                'event_title' => 'required |string',
                'event_details' => 'string |required',
                'host_organization' => 'required | string ',
                'event_coordinator_name'  => 'required | string',
                'event_coordinator_email' => 'required |email |string ',
                'event_coordinator_phone' => 'string',
                'start_date' => 'string | max:50| date_format:Y-m-d',
                'end_date' => 'string | max:50 | date_format:Y-m-d',
                'start_time' => 'string | max:50',
                'end_time' => 'string | max:50',
                'requirements_major' => 'string |max:255',
                'age_requirement' => 'numeric',
                'minimum_hours' => 'numeric',
                'tags' => ' string ',
                'category' => 'required | string',
                'shifts' => 'string',
                'city' => 'string ',
                'address' => 'required',
                'zipcode' => 'numeric | required',
                
                ]);
            $params = [
                'event_title' => htmlentities($request->input('event_title')),
                'event_details' => htmlentities($request->input('event_details')),
                'host_organization' => htmlentities($request->input('host_organization')),
                'event_coordinator_name'  => htmlentities($request->input('event_coordinator_name')),
                'event_coordinator_email'  => htmlentities($request->input('event_coordinator_email')),
                'event_coordinator_phone'  => htmlentities($request->input('event_coordinator_phone')),
                'start_date'  => htmlentities($request->input('start_date')),
                'end_date'  => htmlentities($request->input('end_date')),
                'start_time'  => htmlentities($request->input('start_time')),
                'end_time'  => htmlentities($request->input('end_time')),
                'requirements_major'  => htmlentities($request->input('requirements_major')),
                'age_requirement' => $request->input('age_requirement'),
                'minimum_hours' => $request->input('minimum_hours'),
                'tags'  => htmlentities($request->input('tags','')),
                'category'  => htmlentities($request->input('category')),
                'shifts'  => htmlentities($request->input('shifts')),
                'city'  => htmlentities($request->input('city')),
                'featured' => 0,
                'address'  => htmlentities($request->input('address')),
                'zipcode' => htmlentities(substr($request->input('zipcode'),0,5)),
            ];
            $event = Event::create($params);
            
            if($request->hasFile('file')){
                request()->validate(['file' =>'required', 'file.*' => 'mimes:jpeg,jpg,png,gif']);
                
                $files = $request->file;
                
                foreach($files as $file){
                        $path = $file->store('docs','public');
                        $event_photo = EventPhoto::create(['filename' => $path]);
                        $event->eventPhotos()->save($event_photo);
                        
                }
            }
        }catch(\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            
            
            throw new ValidationException($e->errors());
        }
        
        DB::commit();
        $event= Event::where('id',$event->id)->with('eventPhotos')->first();
        Auth::user()->events()->save($event);
        return $event->refresh();
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Event::with('eventPhotos')->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        
        $event = Event::findOrFail($id);
        
        $user= Auth::user();
        if($event->user->id != $user->id && ($user->rank != 'elevated' && $user->rank != 'root')){
            throw  new AuthorizationException('Unauthorized user');
        }
        
        $request->validate([
            'event_title' => 'string ',
            'event_details' => 'string ',
            'host_organization' => ' string ',
            'event_coordinator_name'  => 'string ',
            'event_coordinator_email' => 'email ',
            'event_coordinator_phone' => 'string',
            'start_date' => 'string |format_date:Y-m-d',
            'end_date' => 'string  | format_date:Y-m-d',
            'start_time' => 'string',
            'end_time' => 'string',
            'requirements_major' => 'string',

            'tags' => 'string ',
            'category' => 'string ',
            'shifts' => 'string ',
            'city' => 'string ',
            'address' => 'string',
            'zipcode' => 'numeric',
        ]);
        $event->update($request->only([
            'event_title',
            'event_details',
            'host_organization',
            'event_coordinator_name',
            'event_coordinator_phone',
            'event_coordinator_email',
            'start_date',
            'end_date',
            'start_time',
            'end_time',
            'requirements_major',
            'tags',
            'category',
            'shifts',
            'city',
            'address',
            'zipcode',
        ]));
        return($event);
    }
    
    

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $user= Auth::user();
        
        if($event->user->id != $user->id && $user->rank != 'elevated' && $user->rank != 'root'){
            throw  new AuthorizationException('Unauthorized user');
        }
        $event->delete();
        return response()->noContent(200);
    }
}
