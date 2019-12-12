<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Zipcode;

class Event extends Model
{
    use FullTextSearch;
    use SoftDeletes;
    
    //attributes that can be directly assigned
    protected $fillable = [
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
        'age_requirement',
        'minimum_hours',
        'tags',
        'category',
        'featured',
        'shifts',
        'city',
        'address',
        'zipcode',
    ];
    //attributes that will be used for the full text search, must be indexed in table creation
    protected $searchable = [
        'tags',
        'event_details',
        'host_organization',
        'event_title',
        'requirements_major',
        'category'

    ];
    
    
    // fixes the zip codes as this mysql data type
    protected $casts =[
        'lat' => 'decimal:5',
        'lon' => 'decimal:5'
    ];
    
    
    // search query for events with in a range of a given zip code
    public function scopeZipcode($query, $range, $zipcode){
        $zip = Zipcode::where('zipcode', '=', $zipcode)->first();
        if($zip == null){
            //if zip code is not found, return an empty set
            return $query->where('date','1900-10-10')->where('date','1900-12-12');
        }
        
        // the haversine calculation for distance between two points on a sphere
        $haversine = "ROUND(3959 * acos(cos(radians($zip->lat)) * cos(radians(lat)) * cos(radians(lon) - radians($zip->lon)) + sin(radians($zip->lat)) * sin(radians(lat))),4) AS distance ";
        return $query
        ->select('*')
        ->selectRaw($haversine)
        ->havingRaw("distance < ?", [$range]);
        //->orderBy("distance","asc");
    }
    //shortcut for this query string
    public function scopeDaterange($query,$date){
        return $query->where('start_date', '<=', $date)->where('end_date','>=',$date);
    }
    //when ever an event is created, associate the event with a longitude latitude found in the database for a zip code
    public function setZipcodeAttribute($value){
        
        $this->attributes['zipcode'] = $value;
        $zipObj = Zipcode::where('zipcode', '=', $value)->first();
        if($zipObj == null){
            //fails gracefully if zipcode not found and is put outside of range of the us
            $this->attributes['lon'] = 90.;
            $this->attributes['lat'] = -99.;
        }else{
        $this->attributes['lon'] = $zipObj->lon;
        $this->attributes['lat'] = $zipObj->lat;
        }
        
    }
    
    // asserts foreign key relationship
    public function user(){
        return $this->belongsTo('App\User');
    }
        //asserts foreign key relationship
    public function eventPhotos(){
        return $this->hasMany('App\EventPhoto');
    }
    
}
