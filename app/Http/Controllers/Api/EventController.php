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
use Illuminate\Pagination;
use Illuminate\Pagination\LengthAwarePaginator;


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
        
        //asserts the sort by parameter
        $query = Event::with('eventPhotos');
        $orderBy = $request->input('orderby','start_date');
        if($orderBy=='distance'){
            $query= $query->orderBy('distance','asc');
        }else{
            $query=$query->orderBy($orderBy);
        }
        // builds query depending on parameters of request
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
        
        // returns a specific type of output based upon parameters
        if(isset($request->date)){
            $results= $query->daterange($request->date)->with('eventPhotos')->orderByRaw($orderBy)->get();
        }elseif(isset($request->old) && $request->old == 'yes'){
            $results =$query->orderByRaw($orderBy)->get(); 
        }elseif(isset($request->old) && $request->old == 'only'){
            $results = $query->where('end_date','<',date('Y-m-d'))->get();
        }else{
            $results = $query->where('end_date','>=',date('Y-m-d'))->orderByRaw($orderBy)->get();
        }
        $pagen = $request->input('pagen',8);
        $page = $request->input('page',1);
        $searchslice = $results->slice(($page-1) * $pagen, $pagen)->all();
        return new LengthAwarePaginator($searchslice,count($results),$pagen, $page, ['path' => $request->url(), 'query'=> $request->query()]);
    }

    

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       // uses a transaction in case file upload fails
        DB::beginTransaction();
        try{
            // validates request
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
            // puts all input through htmlentities filter
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
            // creates event
            $event = Event::create($params);
            
            
            //creates event photos
            if($request->hasFile('file')){
                request()->validate(['file' =>'required' , 'file.*' => 'mimes:jpeg,jpg,png,gif,svg']);
                
                $files = $request->file;
                
                foreach($files as $file){
                        $path = $file->store('docs','public');
                        $event_photo = EventPhoto::create(['filename' => $path]);
                        $event->eventPhotos()->save($event_photo);
                        
                }
            }
        }catch(\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            
            // rolls back if there is an exception
            throw new ValidationException($e->errors());
        }
        
        DB::commit();
        // associates the event with a user
        $event= Event::where('id',$event->id)->with('eventPhotos')->first();
        Auth::user()->events()->save($event);
        // returns the event
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
        // finds the event
        $event = Event::findOrFail($id);
        //check if user is authorized to view
        $user= Auth::user();
        if($event->user->id != $user->id && ($user->rank != 'elevated' && $user->rank != 'root')){
            throw  new AuthorizationException('Unauthorized user');
        }
        // validate request
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
        
        // update event
        $params = $request->only([
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
        ]);
        foreach($params as $key=>$value){
            $params[$key] = htmlentities($value);
        }
        
        $event->update($params);
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
        //finds event and user
        $event = Event::findOrFail($id);
        $user= Auth::user();
        
        // destroys event
        if($event->user->id != $user->id && $user->rank != 'elevated' && $user->rank != 'root'){
            throw  new AuthorizationException('Unauthorized user');
        }
        $event->delete();
        return response()->noContent(200);
    }
}
