<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventPhoto extends Model
{
    // assingnable attributes
    protected $fillable = ['filename'];
    
    //asserts foreign key relationship
    public function event(){
        return $this->belongsTo('App\Event');
    }
}
