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
        'file'
    ];

    protected $searchable = [
        'tags',
        'event_details',
        'host_organization',
        'event_title',
        'requirements_major',
        'category'

    ];
    
    
    
    protected $casts =[
        'lat' => 'decimal:5',
        'lon' => 'decimal:5'
    ];
    
    public function scopeZipcode($query, $range, $zipcode){
        $zip = Zipcode::where('zipcode', '=', $zipcode)->first();
        if($zip == null){
            return $query->where('date','1900-10-10')->where('date','1900-12-12');
        }
        
        
        $haversine = "ROUND(3959 * acos(cos(radians($zip->lat)) * cos(radians(lat)) * cos(radians(lon) - radians($zip->lon)) + sin(radians($zip->lat)) * sin(radians(lat))),4) AS distance ";
        return $query
        ->select('*')
        ->selectRaw($haversine)
        ->havingRaw("distance < ?", [$range])
        ->orderBy("distance","asc");
    }
    
    public function scopeDaterange($query,$date){
        return $query->where('start_date', '<=', $date)->where('end_date','>=',$date);
    }
    
    public function setZipcodeAttribute($value){
        
        $this->attributes['zipcode'] = $value;
        $zipObj = Zipcode::where('zipcode', '=', $value)->first();
        if($zipObj == null){
            $this->attributes['lon'] = 90.;
            $this->attributes['lat'] = -99.;
        }else{
        $this->attributes['lon'] = $zipObj->lon;
        $this->attributes['lat'] = $zipObj->lat;
        }
        
    }
    
    
    public function user(){
        return $this->belongsTo('App\User');
    }
        
    public function eventPhotos(){
        return $this->hasMany('App\EventPhoto');
    }
    
}
