<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventPhoto extends Model
{
    protected $fillable = ['filename'];
    
    public function event(){
        return $this->belongsTo('App\Event');
    }
}
